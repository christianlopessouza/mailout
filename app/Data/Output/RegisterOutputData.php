<?php

namespace App\Data\Output;

use App\Domain\Entities\Account;

class RegisterOutputData
{
    public function __construct(
        public readonly Account $account
    ) {
    }
}
