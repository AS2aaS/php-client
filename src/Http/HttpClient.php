<?php

declare(strict_types=1);

namespace AS2aaS\Http;

use AS2aaS\Exceptions\AS2AuthenticationError;
use AS2aaS\Exceptions\AS2Error;
use AS2aaS\Exceptions\AS2NetworkError;
use AS2aaS\Exceptions\AS2RateLimitError;
use AS2aaS\Exceptions\AS2ValidationError;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    private GuzzleClient $client;
    private array $config;
    private ?string $currentTenantId = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        
        $this->client = new GuzzleClient([
            'base_uri' => $config['baseUrl'],
            'timeout' => ($config['timeout'] ?? 30000) / 1000, // Convert to seconds
            'headers' => [
                'Authorization' => 'Bearer ' . ($config['apiKey'] ?? ''),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'AS2aaS-PHP/' . $this->getVersion(),
            ],
        ]);
    }

    /**
     * Make GET request
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * Make POST request
     */
    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    /**
     * Make PUT request
     */
    public function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, ['json' => $data]);
    }

    /**
     * Make PATCH request
     */
    public function patch(string $path, array $data = []): array
    {
        return $this->request('PATCH', $path, ['json' => $data]);
    }

    /**
     * Make DELETE request
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Upload file
     */
    public function upload(string $path, array $multipart): array
    {
        return $this->request('POST', $path, ['multipart' => $multipart]);
    }

    /**
     * Download file
     */
    public function download(string $path, array $query = []): string
    {
        $response = $this->requestRaw('GET', $path, ['query' => $query]);
        return $response->getBody()->getContents();
    }

    /**
     * Set tenant ID for subsequent requests
     */
    public function setTenantId(?string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    /**
     * Get current tenant ID
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Make HTTP request with custom options
     */
    public function requestWithOptions(string $method, string $path, array $options = []): array
    {
        $response = $this->requestRaw($method, $path, $options);
        $body = $response->getBody()->getContents();
        
        return json_decode($body, true) ?? [];
    }

    /**
     * Make HTTP request with retry logic
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $response = $this->requestRaw($method, $path, $options);
        $body = $response->getBody()->getContents();
        
        return json_decode($body, true) ?? [];
    }

    /**
     * Make raw HTTP request with retry logic
     */
    private function requestRaw(string $method, string $path, array $options = []): ResponseInterface
    {
        // Add X-Tenant-ID header if tenant is set and path requires it
        if ($this->currentTenantId && $this->pathRequiresTenantHeader($path)) {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                ['X-Tenant-ID' => $this->currentTenantId]
            );
        }

        $retries = $this->config['retries'] ?? 3;
        $retryDelay = $this->config['retryDelay'] ?? 1000;
        
        for ($attempt = 1; $attempt <= $retries + 1; $attempt++) {
            try {
                return $this->client->request($method, $path, $options);
            } catch (ConnectException $e) {
                if ($attempt > $retries) {
                    throw new AS2NetworkError('Connection failed: ' . $e->getMessage(), 0, $e);
                }
                
                // Wait before retry
                usleep($retryDelay * 1000 * $attempt);
                continue;
            } catch (ClientException $e) {
                $this->handleClientError($e);
            } catch (ServerException $e) {
                if ($attempt > $retries) {
                    throw new AS2Error('Server error: ' . $e->getMessage(), 'server_error', $e, $e->getCode());
                }
                
                // Wait before retry for server errors
                usleep($retryDelay * 1000 * $attempt);
                continue;
            } catch (RequestException $e) {
                throw new AS2NetworkError('Request failed: ' . $e->getMessage(), 0, $e);
            }
        }
        
        throw new AS2Error('Maximum retries exceeded', 'max_retries_exceeded');
    }

    /**
     * Handle client errors (4xx)
     */
    private function handleClientError(ClientException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        $errorData = json_decode($body, true) ?? [];
        $message = $errorData['message'] ?? $e->getMessage();
        $code = $errorData['code'] ?? 'unknown_error';
        
        switch ($statusCode) {
            case 401:
                throw new AS2AuthenticationError($message, $code);
                
            case 422:
                throw new AS2ValidationError($message, $code, $errorData['details'] ?? []);
                
            case 429:
                $retryAfter = (int) ($response->getHeader('Retry-After')[0] ?? 60);
                throw new AS2RateLimitError($message, $code, $retryAfter);
                
            default:
                throw new AS2Error($message, $code, null, $statusCode);
        }
    }

    /**
     * Check if path requires X-Tenant-ID header
     * 
     * Most modules need tenant context except: tenants, billing, accounts
     */
    private function pathRequiresTenantHeader(string $path): bool
    {
        $tenantScopedPaths = [
            'partners',
            'messages', 
            'certificates',
            'webhook-endpoints',
            'api-keys'
        ];

        foreach ($tenantScopedPaths as $scopedPath) {
            if (str_starts_with($path, $scopedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client version
     */
    private function getVersion(): string
    {
        // Try to get version from composer.json
        $composerFile = __DIR__ . '/../../composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }
        
        return '1.0.0';
    }
}
