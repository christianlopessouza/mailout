<?php

namespace Database\Seeders;

use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\ClientRepository;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {}
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('clients')->delete();
        $client_list = [];

        $client_list[] = Client::create(
            id: 'b6843333-5a34-4eca-bab4-f30ced256885',
            name: 'Grupo Super',
            token: '2a1c64c1-3ce8-482b-bd7b-5d84ecf8dfdb',
            domain: 'gruposuper.com.br'
        );

        $client_list[] = Client::create(
            id: 'fdf26127-77f9-4fba-8300-fe9a7db7d2b5',
            name: 'Super Estágios',
            token: '11fbf2c8-1845-4ca8-975d-45c8e3e32691',
            domain: 'superestagios.com.br'
        );

        foreach ($client_list as $client) {
            $this->clientRepository->save($client);
        }
    }
}
