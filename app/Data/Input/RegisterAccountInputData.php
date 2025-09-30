<?php

namespace App\Data\Input;

use Spatie\LaravelData\Data;

class RegisterAccountInputData extends Data
{
    public function __construct(
        public readonly string $email_address,
        public readonly string $password,
        public readonly string $host,
        public readonly int $port,
        public readonly ?string $username = null
    ) {}
}
