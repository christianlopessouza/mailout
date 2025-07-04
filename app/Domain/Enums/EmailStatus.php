<?php

namespace App\Domain\Enums;

enum EmailStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
}