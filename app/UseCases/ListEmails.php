<?php

namespace App\UseCases;

use App\Data\EmailFilter;
use App\Errors\FilterEmailError;
use App\Errors\FolderNotFoundError;
use App\Errors\InvalidDateRangeError;
use App\Infrastructure\Persistence\FolderRepository;

class ListEmails
{
    private readonly FolderRepository $folderRepository;

    public function __construct(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    protected function validateFolder(EmailFilter $filter): void
    {
        if ($filter->folder_id) {
            $exists = $this->folderRepository->findById($filter->folder_id);
            if (!$exists) throw new FolderNotFoundError();
        }
    }

    protected function validateDateRanges(EmailFilter $filter): void
    {
        if ($filter->process_start_date && $filter->process_end_date) {
            if ($filter->process_start_date > $filter->process_end_date)
                throw new InvalidDateRangeError();
        }

        if ($filter->read_start_date && $filter->read_end_date) {
            if ($filter->read_start_date > $filter->read_end_date)
                throw new InvalidDateRangeError();
        }
    }

    protected function validateEmailScope(EmailFilter $filter): void
    {
        if (empty($filter->accounts) && !empty($filter->query_email_address)) {
            throw new FilterEmailError("query_email_address é obrigatório quando accounts é usado.");
        }
    }
}
