<?php

namespace App\Errors;

class MissingEmailComplementException extends \Exception
{
    public function __construct(
        string $message = 'Missing email complement',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
