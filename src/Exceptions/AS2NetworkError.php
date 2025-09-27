<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

use Throwable;

/**
 * Network error - connection issues, timeouts, etc.
 */
class AS2NetworkError extends AS2Error
{
    public function __construct(string $message = 'Network error', string $errorCode = 'network_error', ?Throwable $previous = null)
    {
        parent::__construct($message, $errorCode, null, null, true, $previous);
    }
}
