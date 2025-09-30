<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class Credentials extends Data
{
    public function __construct(
        #[Required,Email]
        public readonly string $email_address,
        #[Required]
        public readonly string $password,
        #[Required]
        public readonly string $host,
        #[Required]
        public readonly int $port,
        #[Required]
        public readonly string $username
    ) {
    }
}