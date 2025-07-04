<?php

namespace App\Domain\Enums;

enum Origin: string
{
    case MANUAL = 'manual';
    case TRANSACTION = 'transaction';
}
