<?php

namespace App\UseCases\SendEmail;

use App\Domain\Entities\Email;
use App\Domain\Entities\User;

class SendEmailRequest
{
    private User $user;
    private string $actualEmail;
    private array $email;

    public function __construct(User $user, string $actualEmail, array $email)
    {
        $this->user = $user;
        $this->actualEmail = $actualEmail;
        $this->email = $email;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getActualEmail(): string
    {
        return $this->actualEmail;
    }

    public function getEmail(): array
    {
        return $this->email;
    }
}
