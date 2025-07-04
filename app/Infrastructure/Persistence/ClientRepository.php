<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Client;

interface ClientRepository
{
    public function findById(string $id): ?Client;
    public function save(Client $account);
    public function findByDomain(string $domain): ?Client;
    public function findByToken(string $token): ?Client;
}
