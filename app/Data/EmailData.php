<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class EmailData extends Data
{
    public function __construct(
        public array $to,

        #[Required]
        public string $subject,

        #[Required]
        public string $body,

        public string $origin,

        public ?string $thread_id,

        /**
         * @var array<AttachmentData>
         */
        public ?array $attachments = [],

        public ?array $cc = [],

        public ?array $bcc = [],

        public ?string $reply_to = null,

        public bool $transactional = false,

        public ?object $complements = null
    ) {}

    public static function rules(): array
    {
        return [
            'to' => ['array', 'required'],
            'to.*' => ['email'],

            'cc' => ['array', 'nullable'],
            'cc.*' => ['email'],

            'bcc' => ['array', 'nullable'],
            'bcc.*' => ['email'],
        ];
    }
}
