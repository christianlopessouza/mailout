<?php

namespace App\Domain\Contracts;

use App\Data\EmailSenderSend;

interface IEmailSenderService
{
    /**
     * Envia um email utilizando as credenciais do SMTP informadas.
     *
     * @param \App\Data\EmailSenderSend $params
     * @return bool  Retorna true se o envio for bem-sucedido, false caso contrário.
     */
    public function send(EmailSenderSend $params): bool;
}
