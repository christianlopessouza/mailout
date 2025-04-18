<?

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Folder;

interface FolderRepository
{
    public function findBySlug(string $name): Folder;
}
