<?php

namespace App\UseCases;

use App\Data\Input\ListEmailsByAccountInputData;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;

class ListEmailsByAccount extends ListEmails
{
    public function __construct(
        protected readonly EmailRepository $emailRepository,
        protected readonly FolderRepository $folderRepository,
        protected readonly AccountRepository $accountRepository
    ) {
        parent::__construct($folderRepository);
    }

    public function execute(ListEmailsByAccountInputData $input): array
    {
    
        $account = $input->account;
        
        $filter = $input->filter;
        $filter->accounts = [$account->getId()];
        $this->validateFolder($filter);
        $this->validateDateRanges($filter);
        $this->validateEmailScope($filter);

        return $this->emailRepository->list($filter);
    }
}
