<?php

namespace App\Errors;

class EmailSendFailureError extends \Exception
{
    public function __construct(
        string $message = 'Failed to send email',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}

