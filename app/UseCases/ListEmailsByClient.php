<?php

namespace App\UseCases;

use App\Data\Input\ListEmailsByClientInputData;
use App\Errors\ClientNotFoundError;
use App\Errors\UnauthorizedError;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;

class ListEmailsByClient extends ListEmails
{
    public function __construct(
        protected readonly EmailRepository $emailRepository,
        protected readonly FolderRepository $folderRepository,
        protected readonly ClientRepository $clientRepository,
        protected readonly AccountRepository $accountRepository
    ) {
        parent::__construct($folderRepository);
    }

    public function execute(ListEmailsByClientInputData $input): array
    {
        $filter = $input->filter;
        $client = $input->client;

        $filter->accounts = ($filter->accounts) ?
            $this->validate($client->getId(), $filter->accounts) :
            $filter->accounts = $this->getClientEmails($client->getId());

        $this->validateFolder($filter);
        $this->validateDateRanges($filter);

        return $this->emailRepository->list($filter);
    }

    private function validate(string $client_id, array $accounts): array
    {
        $isEmailsValid = $this->accountRepository->validateClientAuthorization(
            $client_id,
            $accounts
        );

        if (!$isEmailsValid)
            throw new UnauthorizedError();

        return array_values($accounts);
    }

    private function getClientEmails(string $client_id): array
    {
        $client_emails = $this->accountRepository->fetchByClient($client_id);
        $accounts = array_map(function ($account) {
            return $account->getId();
        }, $client_emails);
        return $accounts;
    }
}
