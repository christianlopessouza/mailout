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
            'complement_data' => json_encode($data->complements),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function findByEmailId(string $email_id): ?EmailComplementDTO
    {
        $data = DB::table('email_complements')
            ->where('email_id', $email_id)
            ->first();

        if (!$data) {
            return null;
        }

        return EmailComplementDTO::validateAndCreate([
            'email_id' => $data->email_id,
            'complements' => json_decode($data->complement_data),
        ]);
    }

    public function update(string $email_id, object $complements): void
    {
        DB::table('email_complements')
            ->where('email_id', $email_id)
            ->update([
                'complement_data' => json_encode($complements),
                'updated_at' => now(),
            ]);
    }
}
