<?php

namespace App\Infrastructure\Services;

class SendEmailDTO
{
    private string $id;
    private string $from;
    private array $to;
    private string $subject;
    private string $body;
    private array $cc;
    private array $bcc;
    private array $attachments;
    private string $thread_id;
    private string $status;

    public function __construct(
        string $id,
        string $from,
        array $to,
        string $subject,
        string $body,
        array $cc,
        array $bcc,
        array $attachments,
        string $thread_id
    ) {
        $this->id = $id;
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->attachments = $attachments;
        $this->thread_id = $thread_id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getThreadId(): string
    {
        return $this->thread_id;
    }
}