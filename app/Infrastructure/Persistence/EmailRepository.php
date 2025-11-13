<?php

namespace App\Infrastructure\Persistence;
use App\Data\EmailFilter;
use App\Domain\Entities\Email;
use App\Data\EmailSearchTokens;
use App\Data\PaginatedEmailsData;
use App\Data\PaginationData;

interface EmailRepository
{
    public function save(Email $email): void;

    public function update(string $id, array $data): void;

    public function findById(string $id): ?Email;

    /**
     * @return Email[]
     */
    public function list(EmailFilter $filter): array;

    /**
     * @return Email[]
     */
    public function findByThreadId(string $threadId): array;

    public function saveSearchTokens(EmailSearchTokens $emailTokens): void;

    /**
     *
     * @param EmailFilter[] $filters
     */
    public function findByAccount(string $accountId, array $filters, PaginationData $pagination): PaginatedEmailsData;

    /**
     * Summary of findByClient
     * @param EmailFilter[] $filter
     */
    public function findByClient(string $clientId, array $filters, PaginationData $pagination): PaginatedEmailsData;

    /**
     * @return Email
     */
    public function findByExternalId(string $externalId): ?Email;


}
