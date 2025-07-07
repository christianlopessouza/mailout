<?php

namespace App\Errors;

class InvalidDateRangeError extends \Exception
{
    public function __construct(
        string $message = 'Invalid date range',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
