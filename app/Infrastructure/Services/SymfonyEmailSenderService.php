<?php

namespace App\Infrastructure\Services;

use App\Data\EmailSenderSend;
use Exception;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;

class SymfonyEmailSenderService implements EmailSenderService
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}
    public function send(EmailSenderSend $params): bool
    {
        try {
            $email = $params->email;
            $email_data = $email->getData();
            $credentials = $params->credentials;
            $attachments = $params->attachments;

            $dsn = SymfonyMailerHelper::dsn($credentials);

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);
            $symfonyEmail = (new SymfonyEmail())
                ->from(new Address($credentials->email_address, 'Super Estágios'))
                ->to(...$email_data->getTo())
                ->subject($email_data->getSubject())
                ->text('Super Estágios')
                ->html($email_data->getBody());


            if (!empty($email_data->getCc())) {
                $symfonyEmail->cc(...$email_data->getCc());
            }

            if (!empty($email_data->getBcc())) {
                $symfonyEmail->bcc(...$email_data->getBcc());
            }

            if ($email_data->getReplyTo()) {
                $symfonyEmail->replyTo(
                    $email_data->getReplyTo(),
                );
            }

            if ($attachments) {
                foreach ($attachments as $attachment) {
                    $attachment_path = $this->attachmentService->get($attachment);
                    $symfonyEmail->attachFromPath(
                        $attachment_path,
                        $attachment->getFilename(),
                        $attachment->getMimeType()
                    );
                }
            }

            $domain = substr(strrchr($credentials->email_address, "@"), 1);
            $message_id = $email->getId() . '@' . $domain;
            $symfonyEmail->getHeaders()->addIdHeader('Message-ID', $message_id);
            if ($email->getThreadId()) {
                $symfonyEmail->getHeaders()->addIdHeader('In-Reply-To', $email->getThreadId() . "@" . $domain);
                $symfonyEmail->getHeaders()->addIdHeader('References', $email->getThreadId() . "@" . $domain);
            }
            $mailer->send($symfonyEmail);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
