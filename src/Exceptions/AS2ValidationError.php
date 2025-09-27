<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

/**
 * Validation error - invalid request data or parameters
 */
class AS2ValidationError extends AS2Error
{
    public function __construct(string $message = 'Validation failed', string $errorCode = 'validation_error', $details = null)
    {
        parent::__construct($message, $errorCode, $details, 422, false);
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->details ?? [];
    }
}
