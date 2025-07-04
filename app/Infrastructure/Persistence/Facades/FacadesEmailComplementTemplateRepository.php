<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Infrastructure\Persistence\EmailComplementTemplateDTO;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use Illuminate\Support\Facades\DB;

class FacadesEmailComplementTemplateRepository implements EmailComplementTemplateRepository
{
    public function save(EmailComplementTemplateDTO $email_data): void
    {
        $client_id = $email_data->client_id;
        $template = $email_data->data;
        DB::table('email_complement_templates')
            ->where('client_id', $client_id)
            ->update([
                'data' => json_encode($template)
            ]);
    }

    public function findByClientId(string $client_id): ?EmailComplementTemplateDTO
    {
        $data = DB::table('email_complement_templates')
            ->where('client_id', $client_id)
            ->first();

        if (!$data)
            return null;

        $email_complement_template = EmailComplementTemplateDTO::validateAndCreate($data);

        return $email_complement_template;
    }
}
