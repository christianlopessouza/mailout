<?php

namespace App\Domain;

class EmailVO
{
    private string $from;
    private array $to;
    private ?array $cc;
    private ?array $bcc;
    private string $subject;
    private string $body;
    private ?array $attachments;
    private ?string $reply_to;

    public function __construct(
        string $from,
        array $to,
        string $subject,
        string $body,
        ?array $cc = [],
        ?array $bcc = [],
        ?array $attachments = [],
        ?string $reply_to = null
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->reply_to = $reply_to;
    }


    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): ?array
    {
        return $this->cc;
    }

    public function getBcc(): ?array
    {
        return $this->bcc;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getReplyTo(): ?string
    {
        return $this->reply_to;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }
}
