<?php

namespace App\UseCases\ListEmails;

class ListEmailsResponse
{
    
    public function __construct
    (
        private array $email_list
    ){}

    public function json()
    {
        $emails = [];
        foreach ($this->email_list as $email)
        {
            $emails[] = [
                'id' => $email->getId(),
                'from' => $email->getEmailData()->getFrom(),
                'to' => $email->getEmailData()->getTo(),
                'cc' => $email->getEmailData()->getCc(),
                'bcc' => $email->getEmailData()->getBcc(),
                'subject' => $email->getEmailData()->getSubject(),
                'body' => $email->getEmailData()->getBody(),
                'attachments' => $email->getEmailData()->getAttachments(),
                'threadId' => $email->getEmailData()->getThreadId(),
                'processedAt' => $email->getProcessedAt(),
                'received' => $email->getEmailDirectionEnum(),
                'folder' => $email->getEmailFolderEnum(),
                'isDeleted' => $email->getIsDeleted(),
                'readAt' => $email->getReadAt(),
                'isRead' => $email->getIsRead()
            ];
        }

        return $emails;
    }
}
