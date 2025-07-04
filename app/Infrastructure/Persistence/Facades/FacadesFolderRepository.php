<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\Folder;
use App\Infrastructure\Persistence\FolderRepository;
use Illuminate\Support\Facades\DB;

class FacadesFolderRepository implements FolderRepository
{
    private function map(object $data): Folder
    {
        return Folder::create(
            id: $data->id,
            slug: $data->slug,
            name: $data->name,
            account_id: $data->account_id ?? null
        );
    }

    public function save(Folder $folder): void
    {
        $now = now();
        DB::table('folders')->updateOrInsert(
            ['id' => $folder->getId()],
            [
                'slug' => $folder->getSlug(),
                'name' => $folder->getName(),
                'account_id' => $folder->getAccountId(),
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }

    public function findById(string $id): ?Folder
    {
        $data = DB::table('folders')->where('id', $id)->first();
        if (!$data)
            return null;
        return $this->map($data);
    }

    public function findBySlug(string $slug): ?Folder
    {
        $data = DB::table('folders')->where('slug', $slug)->first();
        if (!$data)
            return null;
        return $this->map($data);
    }
}
