<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\Attachment;
use App\Domain\Enums\AttachmentStatus;
use App\Infrastructure\Persistence\AttachmentRepository;
use Illuminate\Support\Facades\DB;

class FacadesAttachmentRepository implements AttachmentRepository
{
    private function map(object $data): Attachment
    {
        return Attachment::create(
            id: $data->id,
            filename: $data->filename,
            mimetype: $data->mimetype,
            size: $data->size,
            status: AttachmentStatus::from($data->status),
            email_id: $data->email_id,
            attachable_id: $data->attachable_id
        );
    }
    public function findByStatus(string $attachableId, AttachmentStatus $status): ?Attachment
    {
        $data = DB::table('attachments')
            ->where('attachable_id', $attachableId)
            ->where('status', $status->value)
            ->first();

        return $data ? $this->map($data) : null;
    }

    public function save(Attachment $attachment): void
    {
        $now = now();
        DB::table('attachments')->updateOrInsert(
            ['id' => $attachment->getId()],
            [
                'filename' => $attachment->getFilename(),
                'mimetype' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
                'status' => $attachment->getStatus()->value,
                'email_id' => $attachment->getEmailId(),
                'attachable_id' => $attachment->getAttachableId(),
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }
}
