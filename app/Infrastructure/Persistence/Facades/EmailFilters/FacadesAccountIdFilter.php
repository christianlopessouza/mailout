<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesAccountIdFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Value must be an array of account IDs');
        }

        if (empty($value)) {
            return $query;
        }

        // Se for apenas um account_id, usa "="
        if (count($value) === 1) {
            return $query->where('e.account_id', $value[0]);
        }

        // Se for mais de um, usa "IN"
        return $query->whereIn('e.account_id', $value);
    }
}

