<?php

namespace App\Data\Input;

use App\Data\EmailFilter;
use App\Domain\Entities\Client;
use Spatie\LaravelData\Data;

class ListEmailsByClientInputData extends Data
{
    public function __construct(
        public EmailFilter $filter,
        public readonly Client $client
    ) {}
}
