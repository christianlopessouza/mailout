<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\ClientRepository;
use Illuminate\Support\Facades\DB;

class FacadesClientRepository implements ClientRepository
{
    public function map(object $data): Client
    {
        return Client::create(
            name: $data->name,
            token: $data->token,
            domain: $data->domain,
            id: $data->id
        );
    }
    public function save(Client $client): void
    {
        $now = now();
        DB::table('clients')
            ->updateOrInsert(
                ['id' => $client->getId()],
                [
                    'name' => $client->getName(),
                    'token' => $client->getToken(),
                    'domain' => $client->getDomain(),
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
    }

    public function findById(string $id): ?Client
    {
        $data = DB::table('clients')
            ->where('id', $id)
            ->first();

        if (!$data) {
            return null;
        }

        return $this->map($data);
    }

    public function findByDomain(string $domain): ?Client
    {
        $data = DB::table('clients')
            ->where('domain', $domain)
            ->first();

        if (!$data) {
            return null;
        }

        return $this->map($data);
    }

    public function findByToken(string $token): ?Client
    {
        $data = DB::table('clients')
            ->where('token', $token)
            ->first();

        if (!$data) {
            return null;
        }

        return $this->map($data);
    }
}
