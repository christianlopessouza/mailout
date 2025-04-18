<?php

namespace App\Domain\Enums;

enum FilterTypeEnum: string
{
    case FROM = 'from';
    case TO = 'to';
    case ALL = 'all';
}
