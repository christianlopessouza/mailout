<?php

namespace App\Domain\Enums;

enum Folder: string
{
    case INBOX = 'inbox';
    case TRASH = 'trash';
    case SPAM = 'spam';
    case IMPORTANT = 'important';
    case STARRED = 'starred';
    case SENT = 'sent';
}