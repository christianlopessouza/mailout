<?php

namespace App\Data;

use App\Domain\Entities\Email;

class SendEmailServiceResponseData
{
    public function __construct(
        public readonly Email $email
    ) {}
}
