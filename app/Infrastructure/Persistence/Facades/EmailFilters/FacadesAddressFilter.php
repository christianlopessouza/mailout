<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesAddressFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }

        return $query->where(function ($q) use ($value) {
            $q->whereIn('est.type', ['from', 'to', 'cc', 'bcc'])
              ->where('est.value', $value);
        });
    }
} 
