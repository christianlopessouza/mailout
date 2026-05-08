<?php

namespace App\Domain\Contracts;

use App\Domain\Entities\Attachment;

interface IAttachmentService
{
    public function get(Attachment $attachment): string;
    public function store(string $filepath, Attachment $attachment): void;
}
