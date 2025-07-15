<?php

namespace App\Domain\Entities;

use App\Domain\EmailVO;
use App\Domain\Enums\EmailStatus;
use App\Util\UUID;
use PhpParser\Node\Expr\Cast\Bool_;

class EmailQueue
{
    private function __construct(
        public string $id,
        public EmailVO $details,
        public EmailStatus $status,
        public \DateTime $created_at,
        public string $batch_id,
        public ?string $external_id = null,
        public ?string $email_id = null,
        public ?string $flag_id = null
    ) {}

    public static function create(
        string $from,
        array $to,
        string $subject,
        string $body,
        string $batch_id,
        bool $attachments,
        ?string $id = null,
        ?array $cc = [],
        ?array $bcc = [],
        ?string $external_id = null,
        ?string $email_id = null,
        ?string $flag_id = null,
        ?EmailStatus $status = null,
        ?\DateTime $created_at = null,
    ): EmailQueue {
        $emailVO = new EmailVO(
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            subject: $subject,
            body: $body,
            attachments: $attachments,
        );

        return new self(
            id: $id ?? UUID::v7(),
            details: $emailVO,
            status: $status ?? EmailStatus::PENDING,
            created_at: $created_at ?? new \DateTime(),
            batch_id: $batch_id,
            external_id: $external_id,
            email_id: $email_id,
            flag_id: $flag_id
        );
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function getDetails(): EmailVO
    {
        return $this->details;
    }

    public function getStatus(): EmailStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function changeToSent(): void
    {
        $this->status = EmailStatus::SENT;
    }

    public function changeToFailed(): void
    {
        $this->status = EmailStatus::FAILED;
    }

    public function getBatchId(): string
    {
        return $this->batch_id;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }
}
