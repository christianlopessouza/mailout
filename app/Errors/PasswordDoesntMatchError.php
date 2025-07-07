<?php

namespace App\Errors;

class PasswordDoesntMatchError extends \Exception
{
    public function __construct(
        string $message = 'Password doesn\'t match',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
