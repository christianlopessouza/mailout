<?php

namespace App\Infrastructure\Support;

use App\Domain\Contracts\IFilter;

class FilterRegistry
{
    private array $filters = [];

    public function register(string $key, IFilter $filter): void
    {
        $this->filters[$key] = $filter;
    }

    public function get(string $key): ?IFilter
    {
        return $this->filters[$key] ?? null;
    }
}
