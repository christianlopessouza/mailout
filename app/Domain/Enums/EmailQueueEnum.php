<?php

namespace App\Domain\Enums;

enum EmailQueueEnum: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
}