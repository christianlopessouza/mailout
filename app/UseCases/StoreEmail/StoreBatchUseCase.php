<?php

namespace App\UseCases\StoreEmail;

use App\Domain\EmailVO;
use App\Domain\Entities\EmailQueue;
use App\Domain\Enums\EmailQueueEnum;
use App\Domain\Repositories\EmailQueueRepositoryInterface;
use Illuminate\Support\Str;

class StoreBatchUseCase implements StoreBatchUseCaseInterface
{
    private EmailQueueRepositoryInterface $emailRepository;

    public function __construct(EmailQueueRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }
    public function execute(StoreBatchRequest $request)
    {
        $emailsData = $request->getEmails();

        $entities = [];
        foreach ($emailsData as $data) {
            $threadId = !empty($data['threadId'])
                ? $data['threadId']
                : Str::uuid()->toString();
            $entity = new EmailQueue(
                id: Str::uuid()->toString(),
                emailData: new EmailVO(
                    from: $data['from'],
                    to: $data['to'],
                    subject: $data['subject'],
                    body: $data['body'],
                    cc: $data['cc'] ?? [],
                    bcc: $data['bcc'] ?? [],
                    attachments: $data['attachments'] ?? [],
                    threadId: $threadId
                ),
                queueStatus: EmailQueueEnum::PENDING,
                createdAt: now()
            );
            $entities[] = $entity;
        }

        $this->emailRepository->save($entities);

        return "Emails salvos com sucesso";
    }
}