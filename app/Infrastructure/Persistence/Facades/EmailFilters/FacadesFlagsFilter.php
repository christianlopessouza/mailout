<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesFlagsFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Value must be an array');
        }

        return $query->join('email_flags AS ef', 'e.id', '=', 'ef.email_id')
                ->join('flags AS fl', 'ef.flag_id', '=', 'fl.id')
                ->whereIn('fl.name', $value);
    }
}