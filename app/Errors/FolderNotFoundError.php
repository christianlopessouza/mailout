<?php

namespace App\Errors;

class FolderNotFoundError extends \Exception
{
    public function __construct(
        string $message = 'Folder not found',
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
