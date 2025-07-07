<?php


namespace App\Infrastructure\Services;

use App\Errors\MissingEmailComplementException;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use Exception;

class EmailComplementService
{
    public function __construct(
        private readonly EmailComplementTemplateRepository $emailComplementTemplateRepository,
    ) {}

    public function applyTemplateAndSave(object $complements, string $account_id): object
    {
        $template = $this->emailComplementTemplateRepository->findByClientId($account_id);
        if (!$template)
            throw new Exception("Template not found");

        return $this->resolveTemplate(
            json_decode($template->data, true),
            $complements
        );
    }

    private function resolveTemplate(array $template, object $complements_values): array
    {
        foreach ($template as $key => $_) {
            if (!property_exists($complements_values, $key)) {
                throw new MissingEmailComplementException("Missing key: $key");
            }

            $template[$key] = $complements_values->{$key};
        }

        return $template;
    }
}
