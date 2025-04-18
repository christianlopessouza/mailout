<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\EmailQueue;

interface EmailQueueRepositoryInterface
{
    public function save(array $email): array;
    public function getBatchEmails(int $amount): array;
    public function changeStatus(EmailQueue $email): void;
}