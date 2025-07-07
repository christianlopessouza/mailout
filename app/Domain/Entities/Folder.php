<?php

namespace App\Domain\Entities;

use App\Util\UUID;

class Folder
{
    public function __construct(
        private string $id,
        private string $slug,
        private string $name,
        private ?string $account_id = null
    ) {}

    public static function create(
        string $slug,
        string $name,
        ?string $account_id = null,
        ?string $id = null,

    ): Folder {
        $id ??= UUID::v7();
        return new self($id, $slug, $name, $account_id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccountId(): ?string
    {
        return $this->account_id;
    }
}
