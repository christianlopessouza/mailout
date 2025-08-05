<?php

namespace App\UseCases;

use App\Data\Input\SaveEmailComplementInputData;
use App\Data\Output\SaveEmailComplementOutputData;
use App\Domain\Entities\EmailComplementTemplate;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;

class SaveEmailComplement
{
    public function __construct(
        private EmailComplementTemplateRepository $emailComplementTemplateRepository
    ) {}


    public function execute(SaveEmailComplementInputData $input): SaveEmailComplementOutputData
    {
        $client = $input->client;
        $template = $input->template;

        $this->validateType($template);

        $email_complement_template = EmailComplementTemplate::create(
            client_id: $client->getId(),
            template: $template
        );

        $this->emailComplementTemplateRepository->save($email_complement_template);

        $output = SaveEmailComplementOutputData::validateAndCreate([
            'email_complement_template' => $email_complement_template
        ]);

        return $output;
    }

    private function validateType($template): void
    {
        $valid_types = ['string', 'int', 'boolean'];

        foreach ($template as $key => $type_value) {
            if (gettype($type_value) === 'array') {
                throw new \InvalidArgumentException("Invalid type: array is not allowed in '{$key}'");
            }

            if (!in_array($type_value, $valid_types, true)) {
                throw new \InvalidArgumentException("Invalid type: '{$type_value}' is not allowed in '{$key}'");
            }
        }
    }
}
