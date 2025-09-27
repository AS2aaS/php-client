<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

use Exception;
use Throwable;

/**
 * Base AS2 error class
 */
class AS2Error extends Exception
{
    protected string $errorCode;
    protected ?int $statusCode;
    protected $details;
    protected bool $retryable;

    public function __construct(
        string $message = '',
        string $errorCode = 'unknown_error',
        $details = null,
        ?int $statusCode = null,
        bool $retryable = false,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
        $this->retryable = $retryable;
    }

    /**
     * Get error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get error details
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Check if error is retryable
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'details' => $this->getDetails(),
            'retryable' => $this->isRetryable(),
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
