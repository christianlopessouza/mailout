<?php

namespace App\UseCases\Services;

use App\Infrastructure\Persistence\FilterBy;

class EmailFiltersService
{
    public function __construct(
        private readonly iterable $filters
    ) {}

    public function resolveFiltersFromDTO(object $dto): array
    {
        $reflectionClass = new \ReflectionClass($dto);
        $availableFilters = collect($this->filters)
            ->keyBy(fn($filter) => get_class($filter));

        $filters = [];

        // Properties with #[FilterBy]
        foreach ($reflectionClass->getProperties() as $property) {
            $value = $property->getValue($dto);

            if ($value === null) {
                continue;
            }

            $attributes = $property->getAttributes(FilterBy::class);

            if (empty($attributes)) {
                continue;
            }

            $filterClass = $attributes[0]->newInstance()->filterClass;

            if ($availableFilters->has($filterClass)) {
                $filters[] = [$availableFilters[$filterClass], $value];
            } else {
                throw new \InvalidArgumentException("Filter class {$filterClass} not found.");
            }
        }

        // Methods with #[Computed] and #[FilterBy]
        foreach ($reflectionClass->getMethods() as $method) {
            $attributes = $method->getAttributes(FilterBy::class);

            if (empty($attributes)) {
                continue;
            }

            $filterClass = $attributes[0]->newInstance()->filterClass;

            if ($availableFilters->has($filterClass)) {
                $value = $method->invoke($dto);

                if ($value === null) {
                    continue;
                }

                $filters[] = [$availableFilters[$filterClass], $value];
            } else {
                throw new \InvalidArgumentException("Filter class {$filterClass} not found.");
            }
        }

        return $filters;
    }
}