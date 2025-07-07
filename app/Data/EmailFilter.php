<?php

namespace App\Data;

use App\Domain\Enums\Direction;
use Spatie\LaravelData\Data;

class EmailFilter extends Data
{
    public function __construct(
        public ?Direction $direction = null,
        public ?bool $read = null,
        public ?string $folder_id = null,
        public ?\DateTime $process_start_date = null,
        public ?\DateTime $process_end_date = null,
        public ?\DateTime $read_start_date = null,
        public ?\DateTime $read_end_date = null,
        public ?string $order = 'descending',
        public ?array $query_email_address = null,
        public ?array $query_email_address_fields = null,
        public ?array $accounts = null,
        public ?int $limit_per_page = 30,
        public ?int $page = 1
    ) {
    }
    public static function rules(): array
    {
        return [
            'accounts' => ['array', 'nullable'],
            'accounts.*' => ['string'],

            'query_email_address' => ['array', 'nullable'],
            'query_email_address.*' => ['email'],
        ];
    }
}
