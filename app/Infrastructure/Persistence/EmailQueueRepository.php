<?php

namespace App\Infrastructure\Persistence;

use App\Domain\EmailVO;
use App\Domain\Entities\EmailQueue;
use App\Domain\Enums\EmailQueueEnum;
use App\Domain\Repositories\EmailQueueRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EmailQueueRepository implements EmailQueueRepositoryInterface
{
    public function save(array $emails): array
    {
        $data = [];
        foreach ($emails as $email) {
            $emailData = $email->getEmailData();
            $data[] = [
                'id' => $email->getId(),
                'from' => $emailData->getFrom(),
                'to' => $emailData->getTo(),
                'cc' => $emailData->getCc(),
                'bcc' => $emailData->getBcc(),
                'subject' => $emailData->getSubject(),
                'body' => $emailData->getBody(),
                'attachment' => $emailData->getAttachments(),
                'status' => $email->getQueueStatus(),
                'threadId' => $emailData->getThreadId(),
                'createdAt' => now(),
            ];
        }

        DB::table('email_queue')->insert($data);

        return $emails;
    }

    public function getBatchEmails(int $amount): array
    {
        $emails = DB::table('email_queue')
            ->where('status', 'pending')
            ->orderBy('createdAt', 'asc')
            ->limit($amount)->get()->toArray();

        $emailList = [];
        foreach ($emails as $email) {
            $emailList[] = new EmailQueue(
                $email->id,
                new EmailVO(
                    $email->from,
                    $email->to,
                    $email->cc,
                    $email->bcc,
                    $email->subject,
                    $email->body,
                    $email->attachment,
                    $email->threadId
                ),
                EmailQueueEnum::from($email->status),
                $email->createdAt
            );
        }

        return $emailList;
    }

    public function changeStatus(EmailQueue $email): void
    {
        DB::table('email_queue')
            ->where('id', $email->getId())
            ->update(['status' => $email->getQueueStatus()]);
    }
}