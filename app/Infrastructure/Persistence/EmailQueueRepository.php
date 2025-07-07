<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\EmailQueue;

interface EmailQueueRepository
{
    public function save(EmailQueue $email): bool;
    /**
     * @param EmailQueue[] $emails
     * @return void
     */
    public function saveAll(array $emails): bool;
    public function fetchPending(int $amount): array;
}
