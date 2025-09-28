<?php

declare(strict_types=1);

namespace AS2aaS;

use AS2aaS\Exceptions\AS2AuthenticationError;
use AS2aaS\Exceptions\AS2Error;
use AS2aaS\Http\HttpClient;
use AS2aaS\Modules\Accounts;
use AS2aaS\Modules\Billing;
use AS2aaS\Modules\Certificates;
use AS2aaS\Modules\Messages;
use AS2aaS\Modules\Partners;
use AS2aaS\Modules\Partnerships;
use AS2aaS\Modules\Sandbox;
use AS2aaS\Modules\Tenants;
use AS2aaS\Modules\Utils;
use AS2aaS\Modules\Webhooks;

/**
 * AS2aaS PHP Client
 * 
 * Making AS2 messaging as simple as sending an email.
 */
class Client
{
    private HttpClient $httpClient;
    private array $config;

    // Module instances
    private ?Partners $partners = null;
    private ?Messages $messages = null;
    private ?Certificates $certificates = null;
    private ?Accounts $accounts = null;
    private ?Tenants $tenants = null;
    private ?Webhooks $webhooks = null;
    private ?Billing $billing = null;
    private ?Sandbox $sandbox = null;
    private ?Partnerships $partnerships = null;
    private ?Utils $utils = null;

    /**
     * Default configuration
     */
    private static array $defaultConfig = [
        'timeout' => 30000,
        'retries' => 3,
        'retryDelay' => 1000,
        'defaultMdnMode' => 'async',
        'defaultSigning' => true,
        'defaultEncryption' => true,
        'autoValidateEDI' => false,
    ];

    /**
     * Global configuration overrides
     */
    private static array $globalConfig = [];

    public function __construct($apiKeyOrConfig = null, array $config = [])
    {
        $this->config = $this->resolveConfig($apiKeyOrConfig, $config);
        $this->httpClient = new HttpClient($this->config);
        
        $this->validateApiKey();
    }

    /**
     * Create client for testing environment
     * 
     * This is just a convenience method for clarity - the API automatically
     * detects test vs live from your key prefix.
     */
    public static function createTest(string $apiKey, array $config = []): self
    {
        return new self($apiKey, $config);
    }

    /**
     * Create mock client for testing
     * 
     * Returns a fully functional mock client that simulates AS2aaS operations
     * without making actual API calls. Perfect for unit testing.
     */
    public static function createMock(): \AS2aaS\Testing\MockClient
    {
        return new \AS2aaS\Testing\MockClient();
    }

    /**
     * Set global configuration defaults
     */
    public static function configure(array $config): void
    {
        self::$globalConfig = array_merge(self::$globalConfig, $config);
    }

    /**
     * Get Partners module
     */
    public function partners(): Partners
    {
        return $this->partners ??= new Partners($this->httpClient);
    }

    /**
     * Get Messages module
     */
    public function messages(): Messages
    {
        return $this->messages ??= new Messages($this->httpClient);
    }

    /**
     * Get Certificates module
     */
    public function certificates(): Certificates
    {
        return $this->certificates ??= new Certificates($this->httpClient);
    }

    /**
     * Get Accounts module (Enterprise)
     */
    public function accounts(): Accounts
    {
        return $this->accounts ??= new Accounts($this->httpClient);
    }

    /**
     * Get Tenants module
     */
    public function tenants(): Tenants
    {
        return $this->tenants ??= new Tenants($this->httpClient);
    }

    /**
     * Get Webhooks module
     */
    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks($this->httpClient);
    }

    /**
     * Get Billing module (Enterprise)
     */
    public function billing(): Billing
    {
        return $this->billing ??= new Billing($this->httpClient);
    }

    /**
     * Get Sandbox module (Testing)
     */
    public function sandbox(): Sandbox
    {
        return $this->sandbox ??= new Sandbox($this->httpClient);
    }

    /**
     * Get Partnerships module (Advanced)
     */
    public function partnerships(): Partnerships
    {
        return $this->partnerships ??= new Partnerships($this->httpClient);
    }

    /**
     * Get Utils module
     */
    public function utils(): Utils
    {
        return $this->utils ??= new Utils($this->httpClient);
    }

    /**
     * Set tenant context for subsequent requests
     */
    public function setTenant(?string $tenantId): self
    {
        $this->httpClient->setTenantId($tenantId);
        return $this;
    }

    /**
     * Get current tenant ID
     */
    public function getCurrentTenant(): ?string
    {
        return $this->httpClient->getCurrentTenantId();
    }

    /**
     * Get HTTP client instance
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Resolve configuration from various input formats
     */
    private function resolveConfig($apiKeyOrConfig, array $config): array
    {
        // Start with defaults and global config
        $resolvedConfig = array_merge(self::$defaultConfig, self::$globalConfig);

        if (is_string($apiKeyOrConfig)) {
            // Simple string API key
            $resolvedConfig['apiKey'] = $apiKeyOrConfig;
            $resolvedConfig = array_merge($resolvedConfig, $config);
        } elseif (is_array($apiKeyOrConfig)) {
            // Configuration array
            $resolvedConfig = array_merge($resolvedConfig, $apiKeyOrConfig, $config);
        } elseif ($apiKeyOrConfig === null) {
            // Try environment variable
            $envKey = $_ENV['AS2AAS_API_KEY'] ?? getenv('AS2AAS_API_KEY');
            if ($envKey) {
                $resolvedConfig['apiKey'] = $envKey;
            }
            $resolvedConfig = array_merge($resolvedConfig, $config);
        }

        // Auto-detect environment from key prefix
        if (isset($resolvedConfig['apiKey']) && !isset($resolvedConfig['environment'])) {
            $resolvedConfig['environment'] = $this->detectEnvironment($resolvedConfig['apiKey']);
        }

        // Set base URL based on environment (baked into client)
        $resolvedConfig['baseUrl'] = $this->getBaseUrl($resolvedConfig['environment'] ?? 'live');

        return $resolvedConfig;
    }

    /**
     * Detect environment from API key prefix
     */
    private function detectEnvironment(string $apiKey): string
    {
        if (str_starts_with($apiKey, 'pk_test_') || str_starts_with($apiKey, 'tk_test_')) {
            return 'test';
        }
        
        return 'live';
    }

    /**
     * Get base URL for environment
     * 
     * Always uses the main production URL as the API automatically
     * detects test vs live keys and routes accordingly.
     */
    private function getBaseUrl(string $environment): string
    {
        return 'https://api.as2aas.com/v1/';
    }

    /**
     * Validate API key format and authentication
     */
    private function validateApiKey(): void
    {
        if (!isset($this->config['apiKey'])) {
            throw new AS2AuthenticationError('API key is required. Provide it directly or set AS2AAS_API_KEY environment variable.');
        }

        $apiKey = $this->config['apiKey'];
        
        // Validate key format
        if (!preg_match('/^(pk|tk)_(live|test)_[a-zA-Z0-9_]+$/', $apiKey)) {
            throw new AS2AuthenticationError('Invalid API key format. Expected format: pk_live_xxx or tk_test_xxx');
        }
    }
}
