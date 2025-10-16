<?php

namespace App\Domain\Enums;

enum AttachmentStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case RECEIVED = 'received';
}
