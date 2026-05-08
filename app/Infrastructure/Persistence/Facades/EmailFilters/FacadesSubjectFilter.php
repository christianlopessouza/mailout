<?php

namespace App\Infrastructure\Persistence\Facades\EmailFilters;

use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;

class FacadesSubjectFilter implements IFilter
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
                ->from('email_search_tokens AS est_subject')
                ->whereRaw('e.id = est_subject.email_id')
                ->where('est_subject.type', 'subject')
                ->whereRaw("est_subject.vector_value @@ plainto_tsquery('portuguese', ?)", ["$value"]);
        });
    }
}
