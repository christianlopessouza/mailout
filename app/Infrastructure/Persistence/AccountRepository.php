<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Account;

interface AccountRepository
{
    public function save(Account $account): void;
    public function fetchByClient(string $client_id): ?array;
    public function validateClientAuthorization(string $client_id, array $email_list);
    public function findById(string $id): ?Account;
    public function findByEmail(string $email): ?Account;
    public function findByToken(string $token): ?Account;
    public function findByUsername(?string $username): ?Account;
}
