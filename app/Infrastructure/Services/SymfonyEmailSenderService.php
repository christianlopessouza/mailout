<?php

namespace App\Infrastructure\Services;

use App\Data\Input\EmailSenderInputData;
use App\Domain\Services\EmailSenderService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SymfonyEmailSenderService implements EmailSenderService
{

    public function send(EmailSenderInputData $params): bool
    {
        try {
            $email = $params->email;
            $credentials = $params->credentials;

            $host = env('MAIL_HOST');
            $port = env('MAIL_PORT');

            $dsn = strtr('smtp://{address}:{password}@{host}:{port}', [
                '{address}' => urlencode($credentials->email_address),
                '{password}' => urlencode($credentials->password),
                '{host}' => $host,
                '{port}' => $port
            ]);

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $symfonyEmail = (new SymfonyEmail())
                ->from($credentials->email_address)
                ->to(...$email->getTo())
                ->subject($email->getSubject())
                ->html($email->getBody());

            if (!empty($email->getCc())) {
                $symfonyEmail->cc(...$email->getCc());
            }

            if (!empty($email->getBcc())) {
                $symfonyEmail->bcc(...$email->getBcc());
            }

            foreach ($email->getAttachments() as $attachment) {
                $symfonyEmail->attachFromPath($attachment);
            }

            $domain = substr(strrchr($credentials->email_address, "@"), 1);
            $message_id = $email->getId() . '@' . $domain;
            $symfonyEmail->getHeaders()->addIdHeader('Message-ID', $message_id);
            if ($email->getThreadId()) {
                $symfonyEmail->getHeaders()->addIdHeader('In-Reply-To', $email->getThreadId() . '@superestagios.com.br');
            }

            $mailer->send($symfonyEmail);

            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }
}
