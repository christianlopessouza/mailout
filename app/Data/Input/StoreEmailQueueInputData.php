<?php

namespace App\Data\Input;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class StoreEmailQueueInputData extends Data
{
    public function __construct(
        public string $client_id,

        #[Required]
        public array $emails
    ) {
    }

    public static function rules(): array
    {
        return [
            'emails' => ['array', 'required', 'min:1'],
            'emails.*.external_id' => ['string', 'nullable'],
            'emails.*.from' => ['email', 'required'],
            'emails.*.to' => ['array', 'required', 'min:1'],
            'emails.*.to.*' => ['email'],
            'emails.*.cc' => ['array'],
            'emails.*.cc.*' => ['email'],
            'emails.*.bcc' => ['array'],
            'emails.*.bcc.*' => ['email'],
            'emails.*.attachments' => ['array'],
            'emails.*.subject' => ['string', 'required'],
            'emails.*.body' => ['string', 'required'],
            'emails.*.flag' => ['nullable', 'string']
        ];
    }
}
