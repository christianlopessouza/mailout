<?php

namespace App\Data;

use App\Domain\Entities\Account;
use App\Data\EmailData;
use Spatie\LaravelData\Data;

class SendEmailServiceData extends Data
{
    public function __construct(
        public readonly Account $account,
        public readonly EmailData $email
    ) {}
}
