<?php

namespace App\Domain\Entities;

use App\Domain\Enums\Direction;
use Str;

class Email
{
    public function __construct(
        private string $id,
        private string $from,
        private array $to,
        private array $cc,
        private array $bcc,
        private string $subject,
        private string $body,
        private Direction $direction,
        private bool $read,
        private string $folder_id,
        private string $thread_id,
        private \DateTime $processed_at,
        private ?array $attachments = [],
        private ?\DateTime $read_at = null
    ) {}

    public static function create(
        string $from,
        array $to,
        string $subject,
        string $body,
        string $direction,
        string $folder_id,
        ?string $id = null,
        ?bool $read = false,
        ?\DateTime $read_at = null,
        ?array $cc = [],
        ?array $bcc = [],
        ?array $attachments = [],
        ?string $thread_id = null,
        ?\DateTime $processed_at = null,
    ): Email {
        return new self(
            id: $id ?? Str::uuid()->toString(),
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            subject: $subject,
            body: $body,
            direction: Direction::from($direction),
            read: $read,
            folder_id: $folder_id,
            thread_id: $thread_id ?? Str::uuid()->toString(),
            processed_at: $processed_at ?? new \DateTime(),
            attachments: $attachments,
            read_at: $read_at
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function getRead(): bool
    {
        return $this->read;
    }

    public function getFolderId(): string
    {
        return $this->folder_id;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getThreadId(): string
    {
        return $this->thread_id;
    }

    public function getProcessedAt(): \DateTime
    {
        return $this->processed_at;
    }

    public function getReadAt(): ?\DateTime
    {
        return $this->read_at;
    }

    public function markAsRead(): void
    {
        $this->read = true;
        $this->read_at = new \DateTime();
    }
}
