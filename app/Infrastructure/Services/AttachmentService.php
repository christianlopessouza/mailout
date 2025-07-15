<?php

namespace App\Infrastructure\Services;

use App\Domain\Entities\Attachment;

interface AttachmentService
{
    public function get(Attachment $attachment): string;
    public function store(string $filepath, Attachment $attachment): void;
}
