<?

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;

class EmailData extends Data
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        #[Email]
        public readonly array $to,
        #[Email]
        public readonly array $cc,
        #[Email]
        public readonly array $bcc,
        public readonly array $attachments,
        public readonly ?string $thread_id
    ) {}
}
