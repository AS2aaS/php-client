<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Exceptions\AS2Error;
use AS2aaS\Exceptions\AS2ValidationError;
use AS2aaS\Http\HttpClient;
use AS2aaS\Models\Certificate;

/**
 * Certificates module - Simplified certificate management
 */
class Certificates extends BaseModule
{
    /**
     * Upload and validate certificate
     */
    public function upload(array $certificateData): Certificate
    {
        // Validate required fields
        if (!isset($certificateData['name'])) {
            throw new AS2ValidationError('Certificate name is required');
        }

        if (!isset($certificateData['file'])) {
            throw new AS2ValidationError('Certificate file is required');
        }

        $multipart = [
            [
                'name' => 'name',
                'contents' => $certificateData['name'],
            ],
        ];

        // Add optional fields
        if (isset($certificateData['type'])) {
            $multipart[] = [
                'name' => 'type',
                'contents' => $certificateData['type'],
            ];
        }

        if (isset($certificateData['usage'])) {
            $multipart[] = [
                'name' => 'usage',
                'contents' => $certificateData['usage'],
            ];
        }

        if (isset($certificateData['partnerId'])) {
            $multipart[] = [
                'name' => 'partnerId',
                'contents' => $certificateData['partnerId'],
            ];
        }

        if (isset($certificateData['password'])) {
            $multipart[] = [
                'name' => 'password',
                'contents' => $certificateData['password'],
            ];
        }

        // Handle file upload
        if (is_string($certificateData['file']) && file_exists($certificateData['file'])) {
            // File path
            $multipart[] = [
                'name' => 'certificate',
                'contents' => fopen($certificateData['file'], 'r'),
                'filename' => basename($certificateData['file']),
            ];
        } else {
            // File content
            $multipart[] = [
                'name' => 'certificate',
                'contents' => $certificateData['file'],
                'filename' => 'certificate.pem',
            ];
        }

        $response = $this->httpClient->upload('certificates', $multipart);
        return new Certificate($response);
    }

    /**
     * List certificates with filtering
     */
    public function list(array $options = []): array
    {
        $query = [];

        if (isset($options['type'])) {
            $query['type'] = $options['type'];
        }

        if (isset($options['active'])) {
            $query['active'] = $options['active'] ? '1' : '0';
        }

        if (isset($options['expiringWithin'])) {
            $query['expiringWithin'] = $options['expiringWithin'];
        }

        if (isset($options['partnerId'])) {
            $query['partnerId'] = $options['partnerId'];
        }

        $response = $this->httpClient->get('certificates', $query);

        return array_map(fn($data) => new Certificate($data), $response['data'] ?? []);
    }

    /**
     * Get certificate by ID
     */
    public function get(string $certificateId): Certificate
    {
        $response = $this->httpClient->get("certificates/{$certificateId}");
        return new Certificate($response);
    }

    /**
     * Update certificate
     */
    public function update(string $certificateId, array $data): Certificate
    {
        $response = $this->httpClient->patch("certificates/{$certificateId}", $data);
        return new Certificate($response);
    }

    /**
     * Delete certificate
     */
    public function delete(string $certificateId): bool
    {
        $this->httpClient->delete("certificates/{$certificateId}");
        return true;
    }

    /**
     * Validate certificate and check configuration
     */
    public function validate(string $certificateId): array
    {
        $response = $this->httpClient->post("certificates/{$certificateId}/validate");

        return [
            'valid' => $response['valid'] ?? false,
            'issues' => $response['issues'] ?? [],
            'details' => $response['details'] ?? [],
            'expiryCheck' => $response['expiryCheck'] ?? [],
        ];
    }

    /**
     * Generate new identity certificate
     */
    public function generateIdentity(array $certificateData): Certificate
    {
        // Validate required fields
        $required = ['commonName', 'organization', 'country'];
        foreach ($required as $field) {
            if (!isset($certificateData[$field])) {
                throw new AS2ValidationError("Field '{$field}' is required");
            }
        }

        // Apply defaults
        $data = array_merge([
            'keySize' => 2048,
            'validityDays' => 365,
        ], $certificateData);

        $response = $this->httpClient->post('certificates/generate-identity', $data);
        return new Certificate($response);
    }

    /**
     * Generate Certificate Signing Request
     */
    public function generateCSR(array $csrData): array
    {
        // Validate required fields
        $required = ['commonName', 'organization', 'country'];
        foreach ($required as $field) {
            if (!isset($csrData[$field])) {
                throw new AS2ValidationError("Field '{$field}' is required");
            }
        }

        // Apply defaults
        $data = array_merge([
            'keySize' => 2048,
        ], $csrData);

        $response = $this->httpClient->post('certificates/generate-csr', $data);

        return [
            'csrContent' => $response['csr'] ?? '',
            'privateKey' => $response['privateKey'] ?? '',
            'keySize' => $response['keySize'] ?? 2048,
        ];
    }

    /**
     * Order SSL certificate
     */
    public function orderSSL(array $sslData): array
    {
        // Validate required fields
        $required = ['commonName', 'organization', 'country'];
        foreach ($required as $field) {
            if (!isset($sslData[$field])) {
                throw new AS2ValidationError("Field '{$field}' is required");
            }
        }

        // Apply defaults
        $data = array_merge([
            'validityYears' => 1,
            'certificateType' => 'standard',
        ], $sslData);

        $response = $this->httpClient->post('certificates/order-ssl', $data);

        return [
            'orderId' => $response['orderId'] ?? '',
            'status' => $response['status'] ?? 'pending',
            'estimatedDelivery' => $response['estimatedDelivery'] ?? null,
            'price' => $response['price'] ?? 0,
        ];
    }

    /**
     * Activate certificate
     */
    public function activate(string $certificateId): Certificate
    {
        $response = $this->httpClient->post("certificates/{$certificateId}/activate");
        return new Certificate($response);
    }

    /**
     * Download certificate files
     */
    public function download(string $certificateId, array $options = []): array
    {
        $query = [];

        if (isset($options['format'])) {
            $query['format'] = $options['format'];
        }

        if (isset($options['includeChain'])) {
            $query['includeChain'] = $options['includeChain'] ? '1' : '0';
        }

        if (isset($options['password'])) {
            $query['password'] = $options['password'];
        }

        $content = $this->httpClient->download("certificates/{$certificateId}/download", $query);

        // If saveToPath is specified, save the files
        if (isset($options['saveToPath'])) {
            $this->saveCertificateFiles($content, $options['saveToPath'], $options);
        }

        // Parse the response (assuming it's a ZIP or multipart response)
        return $this->parseCertificateFiles($content);
    }

    /**
     * Save certificate files to disk
     */
    private function saveCertificateFiles(string $content, string $path, array $options): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $format = $options['format'] ?? 'pem';

        switch ($format) {
            case 'pem':
                file_put_contents($path . '/certificate.pem', $content);
                break;
            case 'pkcs12':
                file_put_contents($path . '/certificate.p12', $content);
                break;
            default:
                file_put_contents($path . '/certificate.' . $format, $content);
        }
    }

    /**
     * Parse certificate files from response
     */
    private function parseCertificateFiles(string $content): array
    {
        // This would need to be implemented based on the actual response format
        // For now, return the raw content
        return [
            'certificate' => $content,
            'privateKey' => null, // Would be parsed from multipart response
            'chain' => null, // Would be parsed from multipart response
        ];
    }
}
