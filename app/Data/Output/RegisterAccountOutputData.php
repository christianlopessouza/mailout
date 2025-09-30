<?php

namespace App\Data\Output;

use App\Domain\Entities\Account;
use Spatie\LaravelData\Data;

class RegisterAccountOutputData extends Data
{
    public function __construct(
        public readonly Account $account
    ) {}
}
