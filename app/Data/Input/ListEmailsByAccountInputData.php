<?php

namespace App\Data\Input;

use App\Data\EmailFilter;
use App\Domain\Entities\Account;
use Spatie\LaravelData\Data;

class ListEmailsByAccountInputData extends Data
{
    public function __construct(
        public readonly EmailFilter $filter,
        public readonly Account $account
    ) {}
}
