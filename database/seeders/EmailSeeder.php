<?php

namespace Database\Seeders;

use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Infrastructure\Persistence\EmailRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailSeeder extends Seeder
{
    public function __construct(
        private EmailRepository $emailRepository
    ) {}
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('emails')->delete();
        DB::table('email_complements')->delete();
        DB::table('attachments')->delete();
        DB::table('email_search_tokens')->delete();


        $email_list = [];

        $email_list[] = Email::create(
            id: 'e4d5a5e6-3b4e-425f-8b3f-8c4d3f2f1a2b',
            account_id: '4e600fae-8e69-4a42-80ef-5784d3c1ec36',
            from: 'root@gruposuper.com.br',
            to: ['ti.superest@gmail.com'],
            cc: ['ti@superestagios.com.br'],
            direction: Direction::OUTGOING,
            read: null,
            folder_id: '2901a670-cf1d-493b-8b1b-e080ec097faf',
            thread_id: 'c3f5a5e6-2b4e-425f-8b3f-8c4d3f2f1a2b',
            origin: Origin::MANUAL,
            subject: 'Teste',
            body: '<h1>Corpo do teste</h1>',
            processed_at: new \DateTime('2025-03-17 10:00:00'),
            attachments: false,
        );

        $email_list[] = Email::create(
            id: '8f2a2f1f-3b4e-425f-8b3f-8c4d3f2f1a2b',
            account_id: '4e600fae-8e69-4a42-80ef-5784d3c1ec36',
            from: 'root@superestagios.com.br',
            to: ['root@gruposuper.com.br'],
            bcc: ['ti.superest@gmail.com'],
            direction: Direction::INCOMING,
            read: false,
            folder_id: '6c97bbc3-9869-49dd-a018-1329e83f6afa',
            thread_id: 'c3f5a5e6-2b4e-425f-8b3f-8c4d3f2f1a3b',
            subject: 'Poggers Noggers',
            body: 'Mensagem auxiliar',
            processed_at: new \DateTime('2025-05-17 12:00:00'),
            attachments: false,

        );

        $email_list[] = Email::create(
            id: '989dc1e6-6486-4b59-8162-6d32aa0751e3',
            account_id: 'b485124b-87be-45a7-b65a-491df797da98',
            from: 'anywhere@gmail.com',
            to: ['ti@gruposuper.com.br'],
            direction: Direction::INCOMING,
            read: false,
            folder_id: '6c97bbc3-9869-49dd-a018-1329e83f6afa',
            thread_id: 'c3f5a5e6-2b4e-425f-8b3f-8c4d3f2f1a3b',
            subject: 'Rexona não te abandona',
            body: 'Take me to the church',
            processed_at: new \DateTime('2025-03-17 14:20:10'),
            attachments: false,

        );

        foreach ($email_list as $email) {
            $this->emailRepository->save($email);
        }
    }
}