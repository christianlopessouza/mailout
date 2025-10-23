<?php

namespace App\UseCases\Services;

use App\Data\EmailSenderSend;
use App\Data\SendEmailServiceData;
use App\Data\SendEmailServiceResponseData;
use App\Domain\Entities\Attachment;
use App\Domain\Entities\Email;
use App\Domain\Enums\AttachmentStatus;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Domain\Enums\Origin;
use App\Errors\EmailSendFailureError;
use App\Errors\FolderNotFoundError;
use App\Infrastructure\Persistence\AttachmentRepository;
use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\AttachmentService;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Services\EmailSenderService;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;

class SendEmailService
{
    public function __construct(
        private readonly FolderRepository $folderRepository,
        private readonly EmailSenderService $emailSenderService,
        private readonly EmailRepository $emailRepository,
        private readonly EmailComplementService $emailComplementService,
        private readonly EmailComplementRepository $emailComplementRepository,
        private readonly AttachmentService $attachmentService,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly EmailComplementTemplateRepository $emailComplementTemplateRepository,
    ) {}

    public function execute(SendEmailServiceData $data): SendEmailServiceResponseData
    {
        $account = $data->account;
        $email_input = $data->email;
        $resolved_complements = null;
        $attachments = [];
        $has_attachments = !empty($email_input->attachments);

        $folder = $this->folderRepository->findBySlug(Folder::SENT->value);
        if (!$folder)
            throw new FolderNotFoundError();

        $email = Email::create(
            account_id: $account->getId(),
            from: $account->getEmailAddress(),
            to: $email_input->to,
            cc: $email_input->cc,
            bcc: $email_input->bcc,
            subject: $email_input->subject,
            body: $email_input->body,
            direction: Direction::OUTGOING,
            origin: Origin::from($email_input->origin),
            folder_id: $folder->getId(),
            attachments: $has_attachments,
            reply_to: $email_input->reply_to,
            thread_id: $email_input->thread_id,
            external_id: $email_input->external_id ?? null,
        );

        $this->emailRepository->save($email);

        if ($has_attachments) {
            foreach ($email_input->attachments as $attachment_input) {
                $attachments[] = $attachment = Attachment::create(
                    filename: $attachment_input->filename,
                    mimetype: $attachment_input->mime_type,
                    size: $attachment_input->size,
                    status: AttachmentStatus::SENT,
                    email_id: $email->getId(),
                    attachable_id: $attachment_input->attachable_id ?? null
                );

                $queued = !!$this->attachmentRepository->findByStatus($attachment->getAttachableId(), AttachmentStatus::QUEUED);
                if (!$queued) {
                    $this->attachmentService->store(
                        filepath: $attachment_input->path,
                        attachment: $attachment
                    );
                }

                $this->attachmentRepository->save($attachment);
            }
        }

        if ($email_input->complements) {
            $resolved_complements = $this->emailComplementService->applyTemplateAndSave(
                complements: $email_input->complements,
                client_id:  $account->getId()
            );
        } else {
            // Se não há complements específicos, verifica se existe template para o cliente
            $template = $this->emailComplementTemplateRepository->findByClientId($account->getId());
            if ($template) {
                // Usa o template como complement quando não há complement específico
                $resolved_complements = $template->getTemplate();
            }
        }

        if ($resolved_complements) {
            $email_complements = EmailComplementDTO::validateAndCreate([
                'complements' => $resolved_complements,
                'email_id' => $email->getId()
            ]);
            $this->emailComplementRepository->save($email_complements);
        }

        $sender_params = EmailSenderSend::validateAndCreate([
            'email' => $email,
            'attachments' => $attachments,
            'credentials' => [
                'password' => $account->getPassword(),
                'host' => $account->getHost(),
                'port' => $account->getPort(),
                'username' => $account->getUsername(),
                'email_address' => $account->getEmailAddress()
            ]
        ]);

        $sent_successfuly = $this->emailSenderService->send($sender_params);
        if (!$sent_successfuly) {
            $email->markAsFailed();
            $this->emailRepository->save($email);
            throw new EmailSendFailureError('Failed to send email. Check SMTP credentials and configuration.');
        }

        $response = new SendEmailServiceResponseData(
            email: $email
        );

        return $response;
    }
}