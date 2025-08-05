<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use Illuminate\Support\Facades\DB;

class FacadesEmailComplementRepository implements EmailComplementRepository
{
    public function saveEmailComplement(EmailComplementDTO $data): void
    {
        DB::table('email_complements')->insert([
            'email_id'      => $data->email_id,
            'complement_data' => json_encode($data->complement_data),
            'created_at'    => $data->created_at,
            'updated_at'    => $data->updated_at,
        ]);
    }

    public function saveEmailComplementTemplate(EmailComplementDTO $data): void
    {
        DB::table('email_complements_template')->insert([
            'client_id'     => $data->email_id,       
            'id'            => $data->id,
            'template' => json_encode($data->template_data),
            'created_at'    => $data->created_at,
            'updated_at'    => $data->updated_at,
        ]);
    }
    public function save(EmailComplementDTO $data): void
    {
        $this->saveEmailComplement($data);
    }
}
