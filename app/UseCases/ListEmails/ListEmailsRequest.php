<?php

namespace App\UseCases\ListEmails;

use App\Domain\Enums\EmailFolderEnum;
use App\Domain\Enums\FilterTypeEnum;

class ListEmailsRequest
{
    public function __construct
    (
        private bool $is_internal,
        private int $per_page,
        private int $page,
        private string $email,
        private ?FilterTypeEnum $filter_type = null,
        private ?EmailFolderEnum $folder = null
    ){}

    public function getIsInternal()
    {
        return $this->is_internal;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFilterType()
    {
        return $this->filter_type;
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public function getPerPage()
    {
        return $this->per_page;
    }

    public function getPage()
    {
        return $this->page;
    }
}
