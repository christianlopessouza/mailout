<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Folder;

interface FolderRepository
{
    public function findBySlug(string $name): ?Folder;
    public function save(Folder $folder):void;
    public function findById(string $id): ?Folder;
}
