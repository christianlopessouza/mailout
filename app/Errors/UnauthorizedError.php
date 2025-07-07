<?php

namespace App\Errors;

class UnauthorizedError extends \Exception
{
    public function __construct(
        string $message = 'Unauthorized',
        int $code = 403,
    ) {
        parent::__construct($message, $code);
    }
}
