<?php

namespace App\Infrastructure\Persistence;
use App\Data\EmailFilter;
use App\Domain\Entities\Email;

interface EmailRepository
{
    public function save(Email $email): void;

    /**
     * @return Email[]
     */
    public function list(EmailFilter $filter): array;
}