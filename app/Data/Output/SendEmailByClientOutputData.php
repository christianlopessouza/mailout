<?php

namespace App\Data\Output;

use App\Domain\Entities\Email;

class SendEmailByClientOutputData
{
    public function __construct(
        public readonly Email $email
    ) {}
}
