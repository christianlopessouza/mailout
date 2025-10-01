<?php

namespace App\Data\Input;


use Spatie\LaravelData\Data;

class ListEmailByIdInputData extends Data
{
    public function __construct(
        public readonly string $id
    ) {}
}
