<?php

namespace App\Domain\Contracts;

interface IFilter
{
    public function apply(mixed $query, mixed $value): mixed;
}
