<?php

namespace App\Infrastructure\Support;

class EmailFiltersMapper
{
    public function __construct(
        private readonly FilterRegistry $filterRegistry
    ) {
    }

    public function resolve(object $dto): array
    {
        $reflectionClass = new \ReflectionClass($dto);
        $filters = [];

        // Properties with #[Filter]
        foreach ($reflectionClass->getProperties() as $property) {
            $value = $property->getValue($dto);

            if ($value === null) {
                continue;
            }

            $attributes = $property->getAttributes(Filter::class);

            if (empty($attributes)) {
                continue;
            }

            $filterKey = $attributes[0]->newInstance()->key;
            $filter = $this->filterRegistry->get($filterKey);

            if ($filter) {
                $filters[] = [$filter, $value];
            } else {
                throw new \InvalidArgumentException("Filter key {$filterKey} not found.");
            }
        }

        // Methods with #[Computed] and #[Filter]
        foreach ($reflectionClass->getMethods() as $method) {
            $attributes = $method->getAttributes(Filter::class);

            if (empty($attributes)) {
                continue;
            }

            $filterKey = $attributes[0]->newInstance()->key;
            $filter = $this->filterRegistry->get($filterKey);

            if ($filter) {
                $value = $method->invoke($dto);

                if ($value === null) {
                    continue;
                }

                $filters[] = [$filter, $value];
            } else {
                throw new \InvalidArgumentException("Filter key {$filterKey} not found.");
            }
        }

        return $filters;
    }
}
