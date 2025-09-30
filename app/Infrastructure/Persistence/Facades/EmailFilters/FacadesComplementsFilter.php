<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesComplementsFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_object($value)) {
            throw new \InvalidArgumentException('Value must be an object with complements');
        }

        $query->join('email_complements AS ec', 'e.id', '=', 'ec.email_id');
        foreach ($value as $key => $val) {
            $query->whereRaw("ec.complement_data @> ?", [json_encode([$key => $val])]);
        }

        return $query;
    }
}