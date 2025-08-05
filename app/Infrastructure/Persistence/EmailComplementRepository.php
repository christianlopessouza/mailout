<?php

namespace App\Infrastructure\Persistence;

use Spatie\LaravelData\Data;

class EmailComplementDTO extends Data
{
    public string $id;
    public string $email_id;
    public array $complement_data;
    public array $template_data;
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
}

interface EmailComplementRepository
{
    public function save(EmailComplementDTO $complements): void;
    public function saveEmailComplement(EmailComplementDTO $data): void;
    public function saveEmailComplementTemplate(EmailComplementDTO $data): void;
}
