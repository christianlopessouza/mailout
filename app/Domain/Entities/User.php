<?php

namespace App\Domain\Entities;

class User
{
    private string $id;
    private string $username;
    private string $password;
    private array $emailAccounts;

    public function __construct(string $id, string $username, string $password, array $emailAccounts)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->emailAccounts = $emailAccounts;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEmailAccounts(): array
    {
        return $this->emailAccounts;
    }

    public function getDefaultEmail(): EmailAccount|null
    {
        if ($this->getEmailAccounts() == null) {
            return null;
        }
        return $this->getEmailAccounts()[0];
    }
}