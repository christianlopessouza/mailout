<?php

namespace App\UseCases;

use App\Data\Input\SendEmailByClientInputData;
use App\Data\Output\SendEmailOutputData;
use App\Data\SendEmailServiceData;
use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\AccountRepository;
use App\UseCases\Services\SendEmailService;
use Exception;

class SendEmailByClient
{
    public function __construct(
        private readonly SendEmailService $sendEmailService,
        private readonly AccountRepository $accountRepository
    ) {}

    public function execute(SendEmailByClientInputData $input): SendEmailOutputData
    {
        $email_from = $input->email->from;
        $client = $input->client;

        $domain = substr(strrchr($email_from, '@'), 1);
        $is_valid_domain = $domain == $client->getDomain();
        if (!$is_valid_domain) {
            throw new UnauthorizedDomainError();
        }

        $account = $this->accountRepository->findByEmail($email_from);
        $account_exists = !!$account;
        if (!$account_exists) {
            throw new Exception('Account not found');
        }

        unset($input->email->from);
        $send_email = $this->sendEmailService->execute(
            SendEmailServiceData::validateAndCreate([
                'account' => $account,
                'email' => $input->email->toArray()
            ])
        );

        $output = new SendEmailOutputData(
            email: $send_email->email
        );
        return $output;
    }
}
