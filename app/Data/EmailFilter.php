<?

namespace App\Data;

use App\Domain\Enums\Direction;

class EmailFilter
{
    /**
     * @var string[] $from
     * @var string[] $to
     */

    public function __construct(
        public readonly ?Direction $direction = null,
        public readonly ?bool $read = null,
        public readonly ?string $folder_id = null,
        public readonly ?\DateTime $process_start_date = null,
        public readonly ?\DateTime $process_end_date = null,
        public readonly ?\DateTime $read_start_date = null,
        public readonly ?\DateTime $read_end_date = null,
        public readonly ?string $order = 'descending',
        public readonly ?array $query_emails = [],
        public readonly ?array $involved_emails = []
    ) {}
}
