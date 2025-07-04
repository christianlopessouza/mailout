<?php

namespace App\Domain\Entities;

use App\Util\UUID;

class Flag
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $account_id,
        public readonly ?string $client_id
    ) {
        if (($account_id && $client_id) || (!$account_id && !$client_id)) {
            throw new \InvalidArgumentException('Flag should be account or client scoped');
        }
    }

    public static function create(
        string $name,
        ?string $account_id = null,
        ?string $client_id = null,
        ?string $id = null
    ) {
        return new self(
            id: $id ?? UUID::v7(),
            name: $name,
            account_id: $account_id,
            client_id: $client_id,
        );
    }

    public function isAccountScoped(): bool
    {
        return $this->account_id !== null;
    }

    public function isClientScoped(): bool
    {
        return $this->client_id !== null;
    }

    public function getId(): string
    {
        return $this->id;
    }   

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccountId(): ?string
    {
        return $this->account_id;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }
}
