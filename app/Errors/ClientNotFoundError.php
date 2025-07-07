<?php

namespace App\Errors;

class ClientNotFoundError extends \Exception
{
    public function __construct(
        string $message = 'Client not found',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
