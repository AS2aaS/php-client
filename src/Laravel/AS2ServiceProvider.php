<?php

declare(strict_types=1);

namespace AS2aaS\Laravel;

use AS2aaS\Client;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for AS2aaS
 */
class AS2ServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/config/as2aas.php', 'as2aas');

        // Register the AS2 client as singleton
        $this->app->singleton('as2aas', function ($app) {
            $config = $app['config']['as2aas'];
            
            return new Client([
                'apiKey' => $config['api_key'],
                'timeout' => $config['timeout'] ?? 30000,
                'retries' => $config['retries'] ?? 3,
                'retryDelay' => $config['retry_delay'] ?? 1000,
                'defaultMdnMode' => $config['default_mdn_mode'] ?? 'async',
                'defaultSigning' => $config['default_signing'] ?? true,
                'defaultEncryption' => $config['default_encryption'] ?? true,
                'autoValidateEDI' => $config['auto_validate_edi'] ?? false,
            ]);
        });

        // Register alias
        $this->app->alias('as2aas', Client::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/as2aas.php' => config_path('as2aas.php'),
            ], 'as2aas-config');
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return ['as2aas', Client::class];
    }
}
