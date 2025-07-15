<?php

namespace App\Domain\Entities;

use App\Util\UUID;

class EmailComplementTemplate
{
    public function __construct(
        public string $id,
        public string $client_id,
        public \stdClass $template
    ) {}

    public static function create(
        string $client_id,
        \stdClass $template,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? UUID::v7(),
            client_id: $client_id,
            template: $template
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function getTemplate(): \stdClass
    {
        return $this->template;
    }
}
