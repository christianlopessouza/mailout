<?php

namespace App\Domain\Entities;

class EmailAccount
{
    private string $id;
    private string $email;
    private string $password;
    private string $userId;

    public function __construct(string $id, string $email, string $password, string $userId)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->userId = $userId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}