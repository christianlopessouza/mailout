<?php

namespace App\Data;

use App\Data\Credentials;
use App\Domain\Entities\Email;
use Spatie\LaravelData\Data;

class EmailSenderSend extends Data
{
    public function __construct(
        public readonly Email $email,
        /**
         * @var array<Attachment>|null
         */
        public readonly ?array $attachments = null,
        public readonly Credentials $credentials,
    ) {}
}
