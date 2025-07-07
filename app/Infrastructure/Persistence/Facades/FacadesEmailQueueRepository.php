<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\EmailQueue;
use App\Infrastructure\Persistence\EmailQueueRepository;
use App\Domain\Enums\EmailStatus;
use Illuminate\Support\Facades\DB;

class FacadesEmailQueueRepository implements EmailQueueRepository
{
    public function save(EmailQueue $email): bool
    {
        $now = now();
        try {
            $result = DB::table('email_queue')
                ->updateOrInsert(
                    ['id' => $email->getId()],
                    [
                        'from' => $email->getDetails()->getFrom(),
                        'to' => json_encode($email->getDetails()->getTo()),
                        'cc' => json_encode($email->getDetails()->getCc() ?? []),
                        'bcc' => json_encode($email->getDetails()->getBcc() ?? []),
                        'subject' => $email->getDetails()->getSubject(),
                        'body' => $email->getDetails()->getBody(),
                        'attachments' => json_encode($email->getDetails()->getAttachments() ?? []),
                        'status' => $email->getStatus()->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'external_id' => $email->getExternalId(),
                        'batch_id' => $email->getBatchId(),
                    ]
                );

            return (bool) $result;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function saveAll(array $emails): bool
    {
        $now = now();
        try {
            foreach ($emails as $email) {
                DB::table('email_queue')->updateOrInsert(
                    ['id' => $email->getId()],
                    [
                        'from' => $email->getDetails()->getFrom(),
                        'to' => json_encode($email->getDetails()->getTo()),
                        'cc' => json_encode($email->getDetails()->getCc() ?? []),
                        'bcc' => json_encode($email->getDetails()->getBcc() ?? []),
                        'subject' => $email->getDetails()->getSubject(),
                        'body' => $email->getDetails()->getBody(),
                        'attachments' => json_encode($email->getDetails()->getAttachments() ?? []),
                        'status' => $email->getStatus()->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'external_id' => $email->getExternalId(),
                        'batch_id' => $email->getBatchId(),
                    ]
                );
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function fetchPending(int $amount): array
    {
        $rows = DB::table('email_queue')
            ->where('status', EmailStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->limit($amount)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = EmailQueue::create(
                from: $row->from,
                to: json_decode($row->to, true),
                subject: $row->subject,
                body: $row->body,
                batch_id: $row->batch_id,
                id: $row->id,
                cc: json_decode($row->cc, true),
                bcc: json_decode($row->bcc, true),
                attachments: json_decode($row->attachments, true),
                status: EmailStatus::from($row->status),
                created_at: new \DateTime($row->created_at),
                external_id: $row->external_id
            );
        }

        return $result;
    }
}
