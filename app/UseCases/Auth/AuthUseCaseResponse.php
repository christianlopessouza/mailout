<?php

namespace App\UseCases\Auth;

use App\Domain\Entities\User;

class AuthUseCaseResponse
{
    private User $user;
    private array $emailAccounts;
    private string $default_email;

    public function __construct(User $user, array $emailAccounts, string $default_email)
    {
        $this->user = $user;
        $this->emailAccounts = $emailAccounts;
        $this->default_email = $default_email;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEmailAccounts(): array
    {
        return $this->emailAccounts;
    }

    public function getDefaultEmail(): string
    {
        return $this->default_email;
    }

}