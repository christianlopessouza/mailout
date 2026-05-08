<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;

class FacadesReadFilter implements IFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Value must be an array with read status and dates');
        }

        if (!isset($value['read']) || !is_bool($value['read'])) {
            throw new \InvalidArgumentException('Value must contain a boolean "read" key');
        }

        $read = $value['read'] ?? null;
        $startDate = $value['start'] ?? null;
        $endDate = $value['end'] ?? null;

        return match (true) {
            $startDate && $endDate =>
                $query->whereRaw('(e.read_at::DATE >= ? AND e.read_at::DATE <= ?)', [$startDate, $endDate]),
            $startDate && !$endDate =>
                $query->whereRaw('e.read_at::DATE >= ?', [$startDate]),
            $endDate && !$startDate =>
                $query->whereRaw('e.read_at::DATE <= ?', [$endDate]),
            $read !== null && !$startDate && !$endDate =>
                $query->whereRaw('e.read::BOOLEAN = ?', [$read]),
            default => $query
        };
    }
}
