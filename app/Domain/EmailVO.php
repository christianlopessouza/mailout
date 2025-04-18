<?php

namespace App\Domain;

class EmailVO
{
    private string $from;
    private array $to;
    private array $cc;
    private array $bcc;
    private string $subject;
    private string $body;
    private array $attachments;
    private ?string $threadId;

    public function __construct(
        string $from,
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $body,
        array $attachments,
        ?string $threadId = null
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->threadId = $threadId;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
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

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }
}