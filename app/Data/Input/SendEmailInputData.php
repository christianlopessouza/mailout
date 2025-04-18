<?php

namespace App\Data;

use App\Domain\Entities\Account;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Required;

class SendEmailInputData extends Data
{
    public function __construct(
        public readonly Account $account,
        public readonly EmailData $email
    ) {}
}

class EmailData extends Data
{
    public function __construct(
        #[Rule(['array', 'nullable', 'each:email'])]
        public array $to,

        #[Rule(['array', 'nullable', 'each:email'])]
        public array $cc = [],

        #[Rule(['array', 'nullable', 'each:email'])]
        public array $bcc = [],

        #[Required]
        public string $subject,

        #[Required]
        public string $body,

        public array $attachments = [],
    ) {}
}
