<?php

namespace App\UseCases\SendEmail;

class SendEmailResponse
{
    private string $message;
    private int $status;

    public function __construct(string $message, int $status)
    {
        $this->message = $message;
        $this->status = $status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function json(): array
    {
        return [
            'message' => $this->message,
            'status' => $this->status,
        ];
    }
}