<?php

namespace App\Domain\Services;

use App\Domain\Entities\EmailQueue;
use App\Infrastructure\Persistence\Facades\FacadesEmailQueueRepository;
use App\Domain\Enums\EmailStatus;
use App\Util\UUID;

class EmailQueueService
{
    private $emailQueueRepository;

    public function __construct(FacadesEmailQueueRepository $emailQueueRepository)
    {
        $this->emailQueueRepository = $emailQueueRepository;
    }

    public function saveEmailToQueue(array $emailData): bool
    {
        $emailQueue = EmailQueue::create(
            from: $emailData['from'],
            to: $emailData['to'],
            subject: $emailData['subject'],
            body: $emailData['body'],
            batch_id: UUID::v4(), 
            cc: $emailData['cc'] ?? [],
            bcc: $emailData['bcc'] ?? [],
            attachments: $emailData['attachments'] ?? [],
            status: EmailStatus::PENDING,
            created_at: new \DateTime(),
            external_id: $emailData['external_id'] ?? null
        );

        return $this->emailQueueRepository->save($emailQueue);
    }
}
