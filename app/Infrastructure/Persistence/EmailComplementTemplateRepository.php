<?php

namespace App\Infrastructure\Persistence;

use Spatie\LaravelData\Data;

class EmailComplementTemplateDTO extends Data
{
    public string $client_id;
    public object $data;
}

interface EmailComplementTemplateRepository
{
    public function save(EmailComplementTemplateDTO $complements): void;
    public function findByClientId(string $client_id): ?EmailComplementTemplateDTO;
}
