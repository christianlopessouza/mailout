<?php

namespace App\UseCases\SendBatch;

use App\Domain\Entities\Email;
use App\Domain\Enums\EmailDirectionEnum;
use App\Domain\Enums\EmailFolderEnum;
use App\Domain\Repositories\EmailQueueRepositoryInterface;
use App\Domain\Repositories\EmailRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\EmailSenderInterface;
use App\Infrastructure\Services\SendEmailDTO;

class SendBatchUseCase implements SendBatchUseCaseInterface
{
    private EmailQueueRepositoryInterface $emailQueueRepository;
    private EmailRepositoryInterface $emailRepository;
    private UserRepositoryInterface $userRepository;
    private EmailSenderInterface $emailSender;

    public function __construct(
        EmailQueueRepositoryInterface $emailQueueRepository,
        UserRepositoryInterface $userRepository,
        EmailSenderInterface $emailSender,
        EmailRepositoryInterface $emailRepository
    ) {
        $this->emailQueueRepository = $emailQueueRepository;
        $this->userRepository = $userRepository;
        $this->emailSender = $emailSender;
        $this->emailRepository = $emailRepository;
    }

    public function execute(SendBatchRequest $request): SendBatchResponse
    {
        $emails = $this->emailQueueRepository->getBatchEmails($request->getAmount());
        if (empty($emails)) {
            return new SendBatchResponse('Nenhum email encontrado');
        }

        foreach ($emails as $email) {
            $valid_user = $this->userRepository->exists($email->getEmailData()->getFrom());
            if (!$valid_user) {
                $email->changeToFailed();
            } else {
                $smtpCredentials = $this->userRepository->getEmailAccount($email->getEmailData()->getFrom());

                $dto = new SendEmailDTO(
                    $email->getId(),
                    $email->getEmailData()->getFrom(),
                    $email->getEmailData()->getTo(),
                    $email->getEmailData()->getSubject(),
                    $email->getEmailData()->getBody(),
                    $email->getEmailData()->getCc(),
                    $email->getEmailData()->getBcc(),
                    $email->getEmailData()->getAttachments(),
                    $email->getEmailData()->getThreadId()
                );

                $sent = $this->emailSender->send($dto, $smtpCredentials);

                if ($sent) {
                    $email->changeToSent();
                } else {
                    $email->changeToFailed();
                }

                $this->emailQueueRepository->changeStatus($email);
            }
            $email = new Email(
                $email->getId(),
                $email->getEmailData(),
                now(),
                EmailDirectionEnum::SENT,
                EmailFolderEnum::SENT,
                false,
                null,
                null,
            );
            $this->emailRepository->insertIntoEmail($email);
        }

        return new SendBatchResponse("Emails registrados com sucesso");
    }
}