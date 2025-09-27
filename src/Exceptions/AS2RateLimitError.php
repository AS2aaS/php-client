<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

/**
 * Rate limit error - too many requests
 */
class AS2RateLimitError extends AS2Error
{
    private int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', string $errorCode = 'rate_limit_error', int $retryAfter = 60)
    {
        parent::__construct($message, $errorCode, null, 429, true);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
