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

        // Não faz JOIN aqui pois o repository já faz leftJoin com 'ec'
        // Apenas adiciona as condições WHERE
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                // Monta condicoes OR para qualquer um dos valores
                $query->where(function ($q) use ($key, $val) {
                    foreach ($val as $v) {
                        $q->orWhereRaw("ec.complement_data @> ?", [json_encode([$key => $v])]);
                    }
                });
            } else {
                $query->whereRaw("ec.complement_data @> ?", [json_encode([$key => $val])]);
            }
        }

        return $query;
    }
}
