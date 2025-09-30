<?php

namespace App\Data\Output;

use Spatie\LaravelData\Data;

class FilterEmailsOutputData extends Data
{
    public function __construct(
        /**
         * @var Email[]
         */
        public array $emails,
        public int $total
    ) {}
}
