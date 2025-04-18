<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\EmailAccount;
use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function findByUsername(string $username): ?User;
    public function exists(string $email): bool;
    public function getEmailAccount(string $email): ?EmailAccount;
}