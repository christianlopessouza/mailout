<?php

namespace App\Data\Input;

use App\Domain\Entities\Client;
use Spatie\LaravelData\Data;

class SaveEmailComplementInputData extends Data
{
    public function __construct(
        public readonly object $template,
        public readonly Client $client,

    ) {}
}