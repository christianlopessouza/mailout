<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AttachmentData extends Data
{
    public function __construct(
        public readonly string $filename,
        public readonly string $mime_type,
        public readonly int $size,
        public readonly ?string $path = null,
        public readonly ?string $attachable_id = null
    ) {
        if (!$attachable_id && !$path) {
            throw new \InvalidArgumentException('If attachable_id is not provided, filename, mime_type, size, and path must be provided.');
        }
    }
}
