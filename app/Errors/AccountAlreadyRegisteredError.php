<?php

namespace App\Errors;

class AccountAlreadyRegisteredError extends \Exception
{
    public function __construct(
        string $message = 'Account already registered',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
