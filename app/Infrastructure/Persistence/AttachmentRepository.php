<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Attachment;
use App\Domain\Enums\AttachmentStatus;

interface AttachmentRepository
{
    public function findByStatus(string $attachableId, AttachmentStatus $status): ?Attachment;
    public function save(Attachment $attachment): void;
}
