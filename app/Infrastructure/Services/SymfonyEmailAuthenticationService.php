<?php

namespace App\Infrastructure\Services;

use App\Data\EmailAuthentication;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class SymfonyEmailAuthenticationService implements EmailAuthenticationService
{
    public function authenticate(EmailAuthentication $params): bool
    {
        try {
            $credentials = $params->credentials;

            $dsn = SymfonyMailerHelper::dsn($credentials);

            /** @var SmtpTransport $transport */
            $transport = Transport::fromDsn($dsn);
            $transport->start(); // agora sem erro na IDE

            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }
}
