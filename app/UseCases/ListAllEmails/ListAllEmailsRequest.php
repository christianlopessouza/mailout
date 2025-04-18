<?php

namespace App\UseCases\ListAllEmails;

class ListAllEmailsRequest
{
    private string $emailId;

    public function __construct(string $emailId)
    {
        $this->emailId = $emailId;
    }

    public function getEmailId(): string
    {
        return $this->emailId;
    }

}
