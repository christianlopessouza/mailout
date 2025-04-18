<?php

namespace App\Domain\Enums;

enum EmailEnum: string
{
    case SEEN = 'seen';
    case UNSEEN = 'unseen';
    case DELETED = 'deleted';
}