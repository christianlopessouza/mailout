<?php

namespace Database\Seeders;

use App\Domain\Entities\Folder;
use App\Infrastructure\Persistence\FolderRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FolderSeeder extends Seeder
{
    public function __construct(
        private FolderRepository $folderRepository
    ) {}
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('folders')->delete();

        $folder_list = [];

        $folder_list[] = Folder::create(
            id: '6c97bbc3-9869-49dd-a018-1329e83f6afa',
            slug: 'inbox',
            name: 'Inbox'
        );

        $folder_list[] = Folder::create(
            id: '2901a670-cf1d-493b-8b1b-e080ec097faf',
            slug: 'sent',
            name: 'Sent'
        );

        $folder_list[] = Folder::create(
            id: '216ddda2-38a2-4881-a4db-4cbf9d6d13ba',
            slug: 'trash',
            name: 'Trash'
        );
        
        $folder_list[] = Folder::create(
            id: '5341a90d-2e22-4949-ace8-6b1ba1ce64a8',
            slug: 'transcation',
            name: 'Transaction'
        );

        foreach ($folder_list as $folder) {
            $this->folderRepository->save($folder);
        }
    }
}