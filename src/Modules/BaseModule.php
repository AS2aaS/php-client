<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Http\HttpClient;

/**
 * Base module class
 */
abstract class BaseModule
{
    protected HttpClient $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get HTTP client
     */
    protected function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}
