<?php

namespace App\Errors;

class UnauthorizedDomainError extends \Exception
{
    public function __construct(
        string $message = 'Unauthorized domain',
        int $code = 401,
    ) {
        parent::__construct($message, $code);
    }
}
