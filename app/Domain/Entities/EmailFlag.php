<?

use App\Util\UUID;

class EmailFlag
{
    public function __construct(
        private string $id,
        private string $account_id,
        private string $flag_id,
        private \DateTime $created_at
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmailId(): string
    {
        return $this->account_id;
    }

    public function getFlagId(): string
    {
        return $this->flag_id;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public static function create(
        string $account_id,
        string $flag_id,
        ?string $id = null,
        ?\DateTime $created_at = null
    ): EmailFlag {
        return new self(
            id: $id ?? UUID::v7(),
            account_id: $account_id,
            flag_id: $flag_id,
            created_at: $created_at ?? new \DateTime(),
        );
    }
}
