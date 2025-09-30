<?php

namespace App\Domain\Enums;

enum AccountType: string
{
    case SENDER = 'sender';
    case ACCOUNT = 'account';
}
