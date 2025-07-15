<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\EmailComplementTemplate;

interface EmailComplementTemplateRepository
{
    public function save(EmailComplementTemplate $complements): void;
    public function findByClientId(string $client_id): ?EmailComplementTemplate;
}
