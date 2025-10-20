<?php

namespace App\Data\Input;

use Spatie\LaravelData\Data;

class ListEmailsByThreadIdInputData extends Data
{
    public function __construct(
        public readonly string $thread_id
    ) {}
}
