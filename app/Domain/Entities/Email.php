<?php

namespace App\Domain\Entities;

use App\Domain\EmailVO;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Util\UUID;

class Email
{
    private function __construct(
        private string $id,
        private string $account_id,
        private EmailVO $data,
        private Direction $direction,
        private string $folder_id,
        private string $thread_id,
        private \DateTime $processed_at,
        private bool $deleted,
        private bool $failed,
        private ?Origin $origin = null,
        private ?bool $read = null,
        private ?\DateTime $read_at = null,
        private ?string $external_id = null,
        private ?array $complements = null,
    ) {}

    public static function create(
        string $account_id,
        string $from,
        array $to,
        string $subject,
        string $body,
        Direction $direction,
        string $folder_id,
        bool $attachments,
        ?array $cc = null,
        ?array $bcc = null,
        ?Origin $origin = null,
        ?string $id = null,
        ?bool $read = null,
        ?\DateTime $read_at = null,
        ?string $thread_id = null,
        ?\DateTime $processed_at = null,
        ?string $external_id = null,
        ?bool $deleted = null,
        ?bool $failed = null,
        ?string $reply_to = null,
        ?array $complements = null
    ): Email {
        if ($direction === Direction::INCOMING) {
            if (!is_bool($read))
                throw new \InvalidArgumentException('Read flag must be true or false for incoming emails.');

            if ($read && !$read_at)
                throw new \InvalidArgumentException('Read timestamp is required if the email is marked as read.');

            if ($origin)
                throw new \InvalidArgumentException('Incoming emails cannot have an origin.');
        }

        if ($direction === Direction::OUTGOING) {
            if ($read_at !== null)
                throw new \InvalidArgumentException('Outgoing emails cannot have a read timestamp.');

            if ($read !== false && $read !== null)
                throw new \InvalidArgumentException('Outgoing emails must not define a read flag.');

            if ($origin === null)
                throw new \InvalidArgumentException('Outgoing emails must have an origin.');
        }

        $email_data = new EmailVO(
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            subject: $subject,
            body: $body,
            attachments: $attachments,
            reply_to: $reply_to
        );

        return new self(
            id: $id ?? UUID::v7(),
            account_id: $account_id,
            data: $email_data,
            direction: $direction,
            read: $read ?? false,
            folder_id: $folder_id,
            thread_id: $thread_id ?? UUID::v4(),
            processed_at: $processed_at ?? new \DateTime(),
            read_at: $read_at,
            origin: $origin,
            external_id: $external_id,
            deleted: $deleted ?? false,
            failed: $failed ?? false,
            complements: $complements
        );
    }

    public function markAsFailed(): void
    {
        if ($this->failed)
            return;

        $this->failed = true;
        $this->deleted = true;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getAccountId(): string
    {
        return $this->account_id;
    }


    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function getRead(): ?bool
    {
        return $this->read;
    }

    public function getFolderId(): string
    {
        return $this->folder_id;
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

    public function getOrigin(): ?Origin
    {
        return $this->origin;
    }

    public function getData(): EmailVO
    {
        return $this->data;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function getFailed(): ?bool
    {
        return $this->failed;
    }

    public function getComplements(): ?array
    {
        return $this->complements;
    }

    public function toArray(): array
    {
        // Sanitiza o body para garantir que não quebre o JSON
        $body = $this->data->getBody();
        if (is_string($body)) {
            // Remove caracteres de controle inválidos, exceto quebras de linha e tabs
            $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $body);
            // Garante encoding UTF-8 válido
            $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
        }

        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'from' => $this->data->getFrom(),
            'to' => $this->data->getTo(),
            'cc' => $this->data->getCc(),
            'bcc' => $this->data->getBcc(),
            'subject' => $this->data->getSubject(),
            'body' => $body,
            'attachments' => $this->data->getAttachments(),
            'reply_to' => $this->data->getReplyTo(),
            'direction' => $this->direction->value,
            'folder_id' => $this->folder_id,
            'thread_id' => $this->thread_id,
            'processed_at' => $this->processed_at->format('Y-m-d H:i:s'),
            'read' => $this->read,
            'read_at' => $this->read_at ? $this->read_at->format('Y-m-d H:i:s') : null,
            'origin' => $this->origin?->value,
            'external_id' => $this->external_id,
            'deleted' => $this->deleted,
            'failed' => $this->failed,
            'complements' => $this->complements
        ];
    }
}
