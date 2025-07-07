<?php

namespace App\Data;

use App\Data\Credentials;
use App\Domain\Entities\Email as EmailEntity;
use Spatie\LaravelData\Data;


class EmailSenderSend extends Data
{
    public function __construct(
        public readonly EmailEntity $email,
        public readonly Credentials $credentials,
    ){}
}


