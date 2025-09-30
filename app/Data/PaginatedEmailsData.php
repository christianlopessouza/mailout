<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class PaginatedEmailsData extends Data
{
    public function __construct(
        /**
         * @var Email[]
         */
        public array $items = [],
        public int $total,
        public int $currentPage,
        public int $perPage
    ) {}

    public static function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'total' => ['required','integer','min:0'],
            'currentPage' => ['integer', 'min:1'],
            'perPage' => ['integer', 'min:1', 'max:100']
        ];
    }
}
