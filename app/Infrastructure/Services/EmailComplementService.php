<?php


namespace App\Infrastructure\Services;

use App\Errors\MissingEmailComplementException;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Domain\Entities\EmailComplementTemplate;
use App\Infrastructure\Persistence\EmailComplementDTO;
use Exception;

class EmailComplementService
{

    public function __construct(
        private readonly EmailComplementTemplateRepository $emailComplementTemplateRepository,
    ) {}

    public function applyTemplateAndSave(object $complements, string $client_id): object
    {
        $template = $this->emailComplementTemplateRepository->findByClientId($client_id);
        if (!$template) {
            throw new Exception("Template not found");
        }

        return $this->resolveTemplate(
            template: $template->template,
            complements_values: $complements
        );
    }

    private function resolveTemplate(object $template, object $complements_values): object
    {
        foreach ($template as $key => $_) {
            if (!property_exists($complements_values, $key)) {
                throw new MissingEmailComplementException("Missing key: $key");
            }

            $template->{$key} = $complements_values->{$key};
        }

        return $template;
    }
}
