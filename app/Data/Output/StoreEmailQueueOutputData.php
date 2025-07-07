<?php

namespace App\Data\Output;

use Spatie\LaravelData\Data;

class StoreEmailQueueOutputData extends Data
{
    public function __construct(
        public array $emails
    ) {}

    public static function rules(): array
    {
        return [
            'emails' => ['array', 'required'],
            'emails.*.id' => ['string', 'required'],
            'emails.*.external_id' => ['string', 'nullable'],
        ];
    }
}
