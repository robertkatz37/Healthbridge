<?php

namespace App\Services\Billing;

use Exception;

class StripeGatewayException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $stripeErrorType = 'api_error',
        public readonly int $httpStatus = 500,
    ) {
        parent::__construct($message);
    }
}
