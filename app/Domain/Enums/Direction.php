<?php

namespace App\Domain\Enums;

enum Direction: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
}
