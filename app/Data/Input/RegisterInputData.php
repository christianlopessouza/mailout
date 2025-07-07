<?php

namespace App\Data\Input;

use App\Data\EmailFilter;
use Spatie\LaravelData\Data;

class RegisterInputData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $password_confirmation,
        public readonly string $host,
        public readonly int $port,
        public readonly ?string $username
    ) {}
}
