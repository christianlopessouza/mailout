<?php

namespace App\Domain\Entities;

use App\Util\UUID;

class Client
{
    private function __construct(
        private string $id,
        private string $name,
        private string $token,
        public string $domain
    ) {
    }

    public static function create(
        string $name,
        string $token,
        string $domain,
        ?string $id = null
    ): Client {
        $id ??= UUID::v7();
        $token ??= UUID::v4();

        return new self(
            $id,
            $name,
            $token,
            $domain
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
