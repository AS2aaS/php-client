<?php

declare(strict_types=1);

namespace AS2aaS\Exceptions;

/**
 * Partner error - partner-related issues
 */
class AS2PartnerError extends AS2Error
{
    public function __construct(string $message = 'Partner error', string $errorCode = 'partner_error', $details = null)
    {
        parent::__construct($message, $errorCode, $details, null, false);
    }
}
