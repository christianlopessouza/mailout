<?php

namespace App\Domain\Entities;

use App\Domain\Enums\AccountType;
use App\Util\UUID;



class Account
{
    private function __construct(
        private string $id,
        private string $email_address,
        private string $host,
        private int $port,
        private string $token,
        private string $password,
        private bool $active,
        private AccountType $type,
        private ?string $username
    ) {}

    public static function create(
        string $email_address,
        string $password,
        string $host,
        int $port,
        AccountType $type,
        ?bool $active = null,
        ?string $token = null,
        ?string $id = null,
        ?string $username = null
    ): Account {
        if ($id && !$token)
            throw new \InvalidArgumentException('Token is required');

        $id ??= UUID::v7();
        $token ??= UUID::v4();

        return new self(
            id: $id,
            email_address: $email_address,
            password: $password,
            host: $host,
            port: $port,
            token: $token,
            username: $username,
            type: $type,
            active: $active ?? true
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmailAddress(): string
    {
        return $this->email_address;
    }


    public function getUsername(): ?string
    {
        return $this->username ?? $this->email_address;
    }


    public function getPassword(): string
    {
        return $this->password;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getToken(): string
    {
        return $this->token;
    }
    public function isActive(): bool
    {
        return $this->active;
    }
    public function getType(): AccountType
    {
        return $this->type;
    }
}