<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

/**
 * Authentication error - invalid API key or authentication failure
 */
class AS2AuthenticationError extends AS2Error
{
    public function __construct(string $message = 'Authentication failed', string $errorCode = 'authentication_error')
    {
        parent::__construct($message, $errorCode, null, 401, false);
    }
}
