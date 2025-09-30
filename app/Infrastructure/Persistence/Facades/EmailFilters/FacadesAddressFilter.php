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

        return $query->whereExists(function ($query) use ($value) {
            $query->selectRaw(1)
                ->from('email_search_tokens AS est_address')
                ->whereRaw('e.id = est_address.email_id')
                ->whereIn('est_address.type', ['from', 'to', 'cc'])
                ->whereRaw("est_address.value ILIKE ?", ["%$value%"]);
        });
    }
}