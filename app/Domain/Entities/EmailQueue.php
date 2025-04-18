<?php

namespace App\Domain\Entities;

use App\Domain\EmailVO;
use App\Domain\Enums\EmailQueueEnum;

class EmailQueue
{
    private string $id;
    private EmailVO $emailData;
    private EmailQueueEnum $queueStatus;
    private string $createdAt;

    public function __construct(
        string $id,
        EmailVO $emailData,
        EmailQueueEnum $queueStatus,
        string $createdAt
    ) {
        $this->id = $id;
        $this->emailData = $emailData;
        $this->queueStatus = $queueStatus;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmailData(): EmailVO
    {
        return $this->emailData;
    }

    public function getQueueStatus(): EmailQueueEnum
    {
        return $this->queueStatus;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function changeToSent(): void
    {
        $this->queueStatus = EmailQueueEnum::SENT;
    }

    public function changeToFailed(): void
    {
        $this->queueStatus = EmailQueueEnum::FAILED;
    }
}