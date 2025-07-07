<?php

namespace App\Infrastructure\Services;

use App\Data\EmailSenderSend;
use App\Errors\EmailSendFailureError;
use Exception;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;

class SymfonyEmailSenderService implements EmailSenderService
{

    public function send(EmailSenderSend $params): bool
    {
        try {
            $email = $params->email;
            $email_data = $email->getData();
            $credentials = $params->credentials;

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

            foreach ($email_data->getAttachments() ?? [] as $attachment) {
                $symfonyEmail->attachFromPath($attachment);
            }

            if ($email_data->getReplyTo()) {
                $symfonyEmail->replyTo(
                    $email_data->getReplyTo(),
                    // 'supervisao@superestagios.com.br'
                );
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
