<?php

namespace App\UseCases\StoreEmail;

class StoreBatchRequest
{
    public function __construct(
        private array $emails
    ) {
    }
    public function getEmails(): array
    {
        return $this->emails;
    }
}
