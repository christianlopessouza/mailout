<?php

namespace App\Data;

use App\Data\Credentials;
use Spatie\LaravelData\Data;


class EmailAuthentication extends Data
{
    public function __construct(
        public readonly Credentials $credentials,
    ) {
        if (!$this->credentials->password && !$this->credentials->username) {
            throw new \InvalidArgumentException('Either password or username must be provided.');
        }
    }
}
