<?php

namespace App\Data\Input;

use App\Data\EmailFilterData;
use App\Domain\Entities\Client;
use Spatie\LaravelData\Data;

class FilterEmailsByClientInputData extends Data
{
    public function __construct(
        public Client $client,
        public EmailFilterData $filter
    ) {}
}
