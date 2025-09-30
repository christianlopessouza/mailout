<?php

namespace App\Infrastructure\Persistence;

use Spatie\LaravelData\Data;

class EmailComplementDTO extends Data
{
    public string $email_id;
    public array $complement_data;
}

interface EmailComplementRepository
{
    public function save(EmailComplementDTO $complements): void;
}
