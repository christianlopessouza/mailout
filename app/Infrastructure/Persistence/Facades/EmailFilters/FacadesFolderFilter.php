<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Infrastructure\Persistence\EmailFilter;
use Illuminate\Database\Query\Builder;

class FacadesFolderFilter implements EmailFilter
{
    public function apply(mixed $query, mixed $value): mixed
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }

        if (!$query instanceof Builder) {
            throw new \InvalidArgumentException('For this class, the query must be an Facades Query Builder instance');
        }

        return $query->join('folders AS f', 'e.folder_id', '=', 'f.id')
            ->where('f.slug', $value);
    }
}