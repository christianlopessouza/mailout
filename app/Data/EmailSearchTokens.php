<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EmailSearchTokens extends Data
{
    public function __construct(
        public readonly string $email_id,
        public readonly SearchTokenParams $params
    ) {}
}

class SearchTokenParams extends Data
{
    public function __construct(
        public string $from,
        public ?array $to,
        public ?array $cc,
        public ?array $bcc,
        public string $subject,
        public string $body
    ) {}
}
