<?php

namespace App\Http\Presenters;

use App\Domain\Entities\Email;

class EmailPresenter
{
    public static function present(Email $email): array
    {
        // Sanitiza o body para garantir que não quebre o JSON
        $body = $email->getData()->getBody();
        if (is_string($body)) {
            // Remove caracteres de controle inválidos, exceto quebras de linha e tabs
            $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $body);
            // Garante encoding UTF-8 válido
            $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
        }

        return [
            'id' => $email->getId(),
            'account_id' => $email->getAccountId(),
            'from' => $email->getData()->getFrom(),
            'to' => $email->getData()->getTo(),
            'cc' => $email->getData()->getCc(),
            'bcc' => $email->getData()->getBcc(),
            'subject' => $email->getData()->getSubject(),
            'body' => $body,
            'direction' => $email->getDirection(),
            'folder_id' => $email->getFolderId(),
            'thread_id' => $email->getThreadId(),
            'origin' => $email->getOrigin(),
            'processed_at' => $email->getProcessedAt()->format('Y-m-d H:i:s'),
            'read' => $email->getRead(),
            'read_at' => $email->getReadAt(),
            'attachments' => $email->getData()->getAttachments()
        ];
    }
}
