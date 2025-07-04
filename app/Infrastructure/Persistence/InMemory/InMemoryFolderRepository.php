<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\Folder;
use App\Infrastructure\Persistence\FolderRepository;


class InMemoryFolderRepository implements FolderRepository
{
    /** @var Folder[] */
    private array $data = [];
    public function save(Folder $folder): void
    {
        $found = array_filter($this->data, function ($item) use ($folder) {
            return $item->getId() === $folder->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $folder;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $folder;
        }
    }
    public function findBySlug(string $name): ?Folder
    {
        $finder = array_filter($this->data, function (Folder $folder) use ($name) {
            return $folder->getSlug() === $name;
        });

        $data = $finder[0] ?? null;

        return $data;
    }
    public function findById(string $id): ?Folder
    {
        $finder = array_filter($this->data, function (Folder $folder) use ($id) {
            return $folder->getId() === $id;
        });

        $data = count($finder) ? reset($finder) : null;

        return $data;
    }
}
