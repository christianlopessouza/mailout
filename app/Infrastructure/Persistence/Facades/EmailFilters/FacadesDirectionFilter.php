<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Enums\Direction;
use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesDirectionFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!$value instanceof Direction) {
            throw new \InvalidArgumentException('Value must be a string');
        }

        return $query->where('direction', $value->value);
    }
}