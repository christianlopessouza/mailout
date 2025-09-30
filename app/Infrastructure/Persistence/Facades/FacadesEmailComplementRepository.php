<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use Illuminate\Support\Facades\DB;

class FacadesEmailComplementRepository implements EmailComplementRepository
{
    public function save(EmailComplementDTO $data): void
    {
        $now = new \DateTime();
        DB::table('email_complements')->insert([
            'email_id'      => $data->email_id,
            'complement_data' => json_encode($data->complement_data),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}