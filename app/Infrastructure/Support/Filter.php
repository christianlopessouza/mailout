<?php

namespace App\Infrastructure\Support;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Filter
{
    public function __construct(
        public string $key
    ) {}
}
