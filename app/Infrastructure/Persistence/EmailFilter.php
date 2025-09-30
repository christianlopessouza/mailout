<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Query\Builder;

interface EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed;
}