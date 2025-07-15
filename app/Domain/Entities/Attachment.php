<?php

namespace App\Domain\Entities;

use App\Domain\Enums\AttachmentStatus;
use App\Util\UUID;

class Attachment
{

    private function __construct(
        private string $id,
        private string $filename,
        private string $mimetype,
        private string $size,
        private AttachmentStatus $status,
        private string $email_id,
        private string $attachable_id
    ) {}
    public static function create(
        string $filename,
        string $mimetype,
        string $size,
        AttachmentStatus $status,
        string $email_id,
        ?string $attachable_id = null,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? UUID::v7(),
            filename: $filename,
            mimetype: $mimetype,
            size: $size,
            status: $status,
            email_id: $email_id,
            attachable_id: $attachable_id ?? UUID::v7(),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimeType(): string
    {
        return $this->mimetype;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getStatus(): AttachmentStatus
    {
        return $this->status;
    }

    public function getEmailId(): string
    {
        return $this->email_id;
    }

    public function getAttachableId(): string
    {
        return $this->attachable_id;
    }
}
