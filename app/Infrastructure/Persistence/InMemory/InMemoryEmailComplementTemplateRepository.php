<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\EmailComplementTemplate;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;

class InMemoryEmailComplementTemplateRepository implements EmailComplementTemplateRepository
{
    /** @var EmailComplementTemplate[] */
    private array $data = [];
    public function save(EmailComplementTemplate $template): void
    {
        $found = array_filter($this->data, function ($item) use ($template) {
            return $item->getId() === $template->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $template;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $template;
        }
    }
    public function findByClientId(string $id): ?EmailComplementTemplate
    {
        $finder = array_filter($this->data, function (EmailComplementTemplate $template) use ($id) {
            return $template->getClientId() === $id;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }


}
