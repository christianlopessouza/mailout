<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use Illuminate\Support\Facades\DB;

class FacadesEmailComplementRepository implements EmailComplementRepository
{
    public function save(EmailComplementDTO $data): void
    {
        $email_id = $data->email_id;
        $complement_data = json_encode($data->complement);
        DB::table('email_complements')->insert([
            'email_id' => $email_id,
            'template_data' => json_encode($complement_data),
        ]);
    }
}
