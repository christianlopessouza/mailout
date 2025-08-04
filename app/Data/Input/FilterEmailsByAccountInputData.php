<?php

namespace App\Data\Input;

use App\Data\EmailFilterData;
use App\Domain\Entities\Account;
use Spatie\LaravelData\Data;

class FilterEmailsByAccountInputData extends Data
{
    public function __construct(
        public Account $account,
        public EmailFilterData $filter
    ) {}
}
