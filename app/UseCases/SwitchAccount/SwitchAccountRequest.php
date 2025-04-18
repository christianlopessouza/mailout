<?php

namespace App\UseCases\SwitchAccount;

use App\Domain\Entities\User;

class SwitchAccountRequest
{
    private User $user;
    private string $emailId;

    public function __construct(User $user, string $emailId)
    {
        $this->user = $user;
        $this->emailId = $emailId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEmailId(): string
    {
        return $this->emailId;
    }
    
}