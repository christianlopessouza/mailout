<?php

namespace App\Errors;

class EmailQueueEmptyError extends \Exception
{
    public function __construct(
        string $message = 'Email queue should not be empty',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
