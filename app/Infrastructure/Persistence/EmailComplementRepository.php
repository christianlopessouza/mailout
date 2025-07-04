<?php

namespace App\Infrastructure\Persistence;

use Spatie\LaravelData\Data;

class EmailComplementDTO extends Data
{
    public string $email_id;
    public object $complement;
}

interface EmailComplementRepository
{
    public function save(EmailComplementDTO $complements): void;
}
