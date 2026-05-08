<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;

class FacadesOrderFilter implements IFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }

        $order_by = $value['order_by'] ?? 'created';
        $order = $value['order'] ?? 'desc';

        return $query->orderBy("e.{$order_by}_at", $order);
    }
}
