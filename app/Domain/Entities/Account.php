<?php

namespace App\Domain\Entities;

class Account
{
    private function __construct(
        private string $id,
        private string $email_address,
        private string $password
    ) {}

    public static function create(
        ?string $id,
        string $email_address,
        string $password
    ): Account
    {
        $id ??= uniqid();
        
        return new self(
            $id,
            $email_address,
            $password
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

    public function getPassword(): string
    {
        return $this->password;
    }
}

