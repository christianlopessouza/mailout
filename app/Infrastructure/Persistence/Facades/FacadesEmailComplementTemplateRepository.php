<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\EmailComplementTemplate;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use Illuminate\Support\Facades\DB;


class FacadesEmailComplementTemplateRepository implements EmailComplementTemplateRepository
{

    private function map(object $data): EmailComplementTemplate
    {
        return EmailComplementTemplate::create(
            id: $data->id,
            client_id: $data->client_id,
            template: json_decode($data->template),
        );
    }
    public function save(EmailComplementTemplate $email_data): void
    {
        $client_id = $email_data->client_id;
        $template = $email_data->template;
        $exists = DB::table('email_complements_template')->where('client_id', $client_id)->exists();

        if ($exists) {
            DB::table('email_complements_template')
                ->where('client_id', $client_id)
                ->update([
                    'template' => json_encode($template),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('email_complements_template')
                ->insert([
                    'id' => $email_data->id,
                    'client_id' => $client_id,
                    'template' => json_encode($template),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function findByClientId(string $client_id): ?EmailComplementTemplate
    {
        $data = DB::table('email_complements_template')
            ->where('client_id', $client_id)  
            ->first();

        if (!$data) {
            return null;
        }

        $template = $this->map($data);

        if (!$this->validateComplementsWithTemplate($template->template, $client_id)) {
            throw new \Exception("Complement type does not match the template for client_id: $client_id");
        }

        return $template;
    }

    private function validateComplementsWithTemplate(object $template, string $email_id): bool
    {
        foreach ($template as $key => $templateValue) {
            $complementValue = $this->getComplementValueForKey($email_id);  

            if ($complementValue === null) {
                throw new \Exception("Complement not found.");
            }

            // Validação do tipo, conforme necessário
            if (!in_array($complementValue, $templateValue)) {
                throw new \Exception("Complement type does not match template.");
            }
        }

        return true;
    }


    private function getComplementValueForKey(string $email_id)
    {
        return DB::table('email_complements')
            ->where('email_id', $email_id)  
            ->value('complement_data');  
    }

}
