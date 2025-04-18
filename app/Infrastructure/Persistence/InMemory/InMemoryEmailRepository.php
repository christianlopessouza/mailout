<?

namespace App\Infrastructure\Persistence\InMemory;

use App\Data\EmailFilter;
use App\Domain\Entities\Email;
use App\Infrastructure\Persistence\EmailRepository;


class InMemoryEmailRepository implements EmailRepository
{
    /** @var Email[] */
    private array $data = [];
    public function save(Email $email): void
    {
        $this->data[] = $email;
    }
    public function list(EmailFilter $filter): array
    {
        throw new \Exception('Not implemented');
    }
}
