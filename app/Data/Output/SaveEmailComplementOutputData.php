<?php

namespace App\Data\Output;

use App\Domain\Entities\EmailComplementTemplate;
use Spatie\LaravelData\Data;

class SaveEmailComplementOutputData extends Data
{
    public function __construct(
        public readonly EmailComplementTemplate $email_complement_template,
    ) {
    }
}