<?php

namespace App\Data\Input;

use App\Data\Credentials;
use Spatie\LaravelData\Data;


class EmailAuthenticationInputData extends Data
{
    public function __construct(
        public readonly Credentials $credentials,
    ) {}
}
