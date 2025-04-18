<?

namespace App\Domain\Entities;

class Folder
{
    public function __construct(
        private int $id,
        private string $slug,
        private string $name,
        private ?string $accont_id = null
    ) {}

    public static function create(
        int $id,
        string $slug,
        string $name
    ): Folder {
        return new self($id, $slug, $name);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string{
        return $this->slug;
    }

    public function getName(): string{
        return $this->name;
    }

    public function getAccountId(): ?string{
        return $this->accont_id;
    }
}
