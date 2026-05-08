<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;

class FacadesProcessDateFilter implements IFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Value must be an array with start and end dates');
        }

        $startDate = $value['start'] ?? null;
        $endDate = $value['end'] ?? null;

        return match (true) {
            $startDate && $endDate =>
                $query->whereRaw('(e.processed_at::DATE >= ? AND e.processed_at::DATE <= ?)', [$startDate, $endDate]),
            $startDate && !$endDate =>
                $query->whereRaw('e.processed_at::DATE >= ?', [$startDate]),
            $endDate && !$startDate =>
                $query->whereRaw('e.processed_at::DATE <= ?', [$endDate]),
            default => $query
        };
    }
}
