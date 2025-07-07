<?php

namespace App\UseCases;

use App\Data\EmailSenderSend;
use App\Data\Output\SendEmailOutputData;
use App\Data\Input\SendEmailInputData;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Domain\Enums\Origin;
use App\Errors\EmailSendFailureError;
use App\Errors\FolderNotFoundError;
use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Services\EmailSenderService;

class SendEmail
{
    public function __construct(
        public readonly EmailSenderService $emailSenderService,
        public readonly EmailRepository $emailRepository,
        public readonly FolderRepository $folderRepository,
        public readonly EmailComplementService $emailComplementService,
        private readonly EmailComplementRepository $emailComplementRepository,
    ) {}

    public function execute(SendEmailInputData $input): SendEmailOutputData
    {
        $account = $input->account;
        $email_data = $input->email_data;
        $resolved_complements = null;

        $folder = $this->folderRepository->findBySlug(Folder::SENT->value);
        if (!$folder)
            throw new FolderNotFoundError();

        $email = Email::create(
            account_id: $account->getId(),
            from: $account->getEmailAddress(),
            to: $email_data->to,
            cc: $email_data->cc,
            bcc: $email_data->bcc,
            subject: $email_data->subject,
            body: $email_data->body,
            direction: Direction::OUTGOING,
            origin: Origin::from($email_data->origin),
            folder_id: $folder->getId(),
            attachments: $email_data->attachments,
            reply_to: $email_data->reply_to,
            thread_id: $email_data->thread_id
        );

        if ($email_data->complements) {
            $resolved_complements = $this->emailComplementService->applyTemplateAndSave(
                $email_data->complements,
                $account->getId()
            );
        }

        $this->emailRepository->save($email);
        if ($resolved_complements) {
            $email_complements = EmailComplementDTO::validateAndCreate([
                'complements' => $resolved_complements,
                'email_id' => $email->getId()
            ]);
            $this->emailComplementRepository->save($email_complements);
        }

        $sender_params = EmailSenderSend::validateAndCreate([
            'email' => $email,
            'credentials' => [
                'email_address' => $account->getEmailAddress(),
                'password' => $account->getPassword(),
                'host' => $account->getHost(),
                'port' => $account->getPort(),
                'username' => $account->getUsername()
            ]
        ]);

        $sent_successfuly = $this->emailSenderService->send($sender_params);
        if (!$sent_successfuly) {
            $email->markAsFailed();
            $this->emailRepository->save($email);
            throw new EmailSendFailureError();
        }

        $output = new SendEmailOutputData(
            email: $email
        );

        return $output;
    }
}
