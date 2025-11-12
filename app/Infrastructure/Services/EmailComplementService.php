<?php


namespace App\Infrastructure\Services;

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
        foreach ($template as $key => $type) {
            if (property_exists($complements_values, $key)) {
                // Se o valor foi informado, usa o valor informado
                $template->{$key} = $complements_values->{$key};
            } else {
                // Se o valor não foi informado, usa o valor padrão baseado no tipo
                $template->{$key} = $this->getDefaultValueByType($type);
            }
        }

        return $template;
    }

    private function getDefaultValueByType(string $type): string|int|bool
    {
        return match ($type) {
            'string' => '',
            'int' => 0,
            'boolean' => false,
            default => '',
        };
    }
}
