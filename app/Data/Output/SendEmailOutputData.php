<?php

namespace App\Data\Output;

use App\Domain\Entities\Email;

class SendEmailOutputData
{
    public function __construct(
        public readonly Email $email
    ) {}
}
