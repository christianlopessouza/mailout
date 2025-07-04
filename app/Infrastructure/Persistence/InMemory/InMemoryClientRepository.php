<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\ClientRepository;

class InMemoryClientRepository implements ClientRepository
{
    /** @var Client[] */
    private array $data = [];
    public function save(Client $client): void
    {
        $found = array_filter($this->data, function ($item) use ($client) {
            return $item->getId() === $client->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $client;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $client;
        }
    }
    public function findById(string $id): ?Client
    {
        $finder = array_filter($this->data, function (Client $client) use ($id) {
            return $client->getId() === $id;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }
    public function findByDomain(string $domain): ?Client
    {
        $finder = array_filter($this->data, function (Client $client) use ($domain) {
            return $client->getDomain() === $domain;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }
    public function findByToken(string $token): ?Client
    {
        $finder = array_filter($this->data, function (Client $client) use ($token) {
            return $client->getToken() === $token;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }

}
