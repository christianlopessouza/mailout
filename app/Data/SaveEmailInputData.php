<?php

namespace App\Data;

use App\Data\AttachmentData;
use App\Domain\Entities\Account;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class SaveEmailInputData extends Data
{
    public function __construct(
        public Account $account,
        public array $to,

        #[Required]
        public string $subject,

        #[Required]
        public string $body,

        public ?string $origin = null,

        public ?string $thread_id = null,

        /**
         * @var array<AttachmentData>
         */
        public ?array $attachments = [],

        public ?array $cc = [],

        public ?array $bcc = [],

        public ?string $reply_to = null,

        public ?string $external_id = null,

        public ?object $complements = null,

        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s')]
        public ?\DateTime $processed_at = null
    ) {}

    public static function rules(ValidationContext $context = null): array
    {
        return [
            'to.*' => ['email'],
            'cc.*' => ['email'],
            'bcc.*' => ['email'],
        ];
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
}
