<?php

namespace App\Infrastructure\Persistence;

use Spatie\LaravelData\Data;

class EmailComplementDTO extends Data
{
    public string $email_id;
    public object $complements;
}

interface EmailComplementRepository
{
    public function save(EmailComplementDTO $complements): void;
    public function findByEmailId(string $email_id): ?EmailComplementDTO;
    public function update(string $email_id, object $complements): void;
}
