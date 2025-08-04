<?php

namespace App\Infrastructure\Persistence;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class FilterBy
{
    public function __construct(
        public string $filterClass
    ) {}
}