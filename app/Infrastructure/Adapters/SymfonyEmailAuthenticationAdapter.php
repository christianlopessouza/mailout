<?php

namespace App\Infrastructure\Adapters;

use App\Domain\Contracts\IEmailAuthenticationService;
use App\Data\EmailAuthentication;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use App\Infrastructure\Services\SymfonyMailerHelper;

class SymfonyEmailAuthenticationAdapter implements IEmailAuthenticationService
{
    public function authenticate(EmailAuthentication $params): bool
    {
        try {
            $credentials = $params->credentials;

            $dsn = SymfonyMailerHelper::dsn($credentials);

            /** @var SmtpTransport $transport */
            $transport = Transport::fromDsn($dsn);
            $transport->start();

            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }
}
