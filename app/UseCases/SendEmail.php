<?php

namespace App\UseCases;

use App\Data\Input\EmailSenderInputData;
use App\Data\Output\SendEmailOutputData;
use App\Data\SendEmailInputData;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Domain\Services\EmailSenderService;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;

class SendEmail
{
    public function __construct(
        public readonly EmailSenderService $emailSenderService,
        public readonly EmailRepository $emailRepository,
        public readonly FolderRepository $folderRepository
    ) {}

    public function execute(SendEmailInputData $input): SendEmailOutputData
    {
        $account = $input->account;
        $email_data = $input->email;

        $folder = $this->folderRepository->findBySlug(Folder::SENT->value);
        if (!$folder)
            throw new \Exception('Folder not found');

        $email = Email::create(
            from: $account->getEmailAddress(),
            to: $email_data->to,
            cc: $email_data->cc,
            bcc: $email_data->bcc,
            subject: $email_data->subject,
            body: $email_data->body,
            direction: Direction::OUTGOING->value,
            folder_id: $folder->getId(),
            attachments: $email_data->attachments,
        );

        $sender_params = EmailSenderInputData::validateAndCreate([
            'email' => $email,
            'account' => [
                'email_address' => $account->getEmailAddress(),
                'password' => $account->getPassword()
            ]
        ]);

        $sent_successfuly = $this->emailSenderService->send($sender_params);
        if (!$sent_successfuly)
            throw new \Exception('Error sending email');


        $this->emailRepository->save($email);

        $output = new SendEmailOutputData(
            email: $email
        );

        return $output;
    }
}
