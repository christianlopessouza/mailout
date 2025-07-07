<?php

namespace App\Domain\Entities;

use App\Util\UUID;

class Account
{
    private function __construct(
        private string $id,
        private string $email_address,
        private string $password,
        private string $host,
        private int $port,
        private string $token,
        private ?string $username
    ) {
    }

    public static function create(
        string $email_address,
        string $password,
        string $host,
        int $port,
        ?string $token,
        ?string $id = null,
        ?string $username = null
    ): Account {
        if ($id && !$token)
            throw new \InvalidArgumentException('Token is required');

        $id ??= UUID::v7();
        $token ??= UUID::v4();

        return new self(
            $id,
            $email_address,
            $password,
            $host,
            $port,
            $token,
            $username
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
        return $this->username;
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
}
