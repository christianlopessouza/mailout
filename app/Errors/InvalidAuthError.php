<?php

namespace App\Errors;

class InvalidAuthError extends \Exception
{
    public function __construct(
        string $message = 'Invalid authentication',
        int $code = 401,
    ) {
        parent::__construct($message, $code);
    }
}
