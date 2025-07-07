<?php

namespace App\Infrastructure\Services;

use App\Data\EmailSenderSend;

interface EmailSenderService
{
    /**
     * Envia um email utilizando as credenciais do SMTP informadas.
     *
     * @param \App\Infrastructure\Services\SendEmailDTO $email
     * @param mixed $smtpCredentials  // Você pode definir uma interface ou DTO para as credenciais
     * @return bool  Retorna true se o envio for bem-sucedido, false caso contrário.
     */
    public function send(EmailSenderSend $params): bool;
}
