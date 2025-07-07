<?php

namespace App\Errors;

class FilterEmailError extends \Exception
{
    public function __construct(
        string $message = 'Failed to filter emails',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
