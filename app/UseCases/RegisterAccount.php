<?php

namespace App\UseCases;

use App\Data\EmailAuthentication;
use App\Data\Input\RegisterAccountInputData;
use App\Data\Output\RegisterAccountOutputData;
use App\Domain\Entities\Account;
use App\Domain\Enums\AccountType;
use App\Errors\AccountAlreadyRegisteredError;
use App\Errors\InvalidAuthError;
use App\Errors\UnauthorizedDomainError;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Services\EmailAuthenticationService;
use App\Util\UUID;

class RegisterAccount
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly ClientRepository $clientRepository,
        private readonly EmailAuthenticationService $emailAuthenticationService
    ) {}
    public function execute(RegisterAccountInputData $data): RegisterAccountOutputData
    {
        if (!filter_var($data->email_address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidAuthError('Email is not valid');
        }

        $account_already_exists = $this->accountRepository->findByEmail($data->email_address);
        if ($account_already_exists) {
            throw new AccountAlreadyRegisteredError();
        }

        $domain = substr(strrchr($data->email_address, '@'), 1);
        $is_domain_valid = $this->clientRepository->findByDomain($domain);
        if (!$is_domain_valid) {
            throw new UnauthorizedDomainError();
        }

        $encrypted_password = Crypto::encrypt($data->password);
        $authentication_params = EmailAuthentication::validateAndCreate([
            'credentials' => [
                'email_address' => $data->email_address,
                'username' => $data->username,
                'password' => $encrypted_password,
                'host' => $data->host,
                'port' => $data->port
            ]
        ]);

        $authentication = $this->emailAuthenticationService->authenticate($authentication_params);
        if (!$authentication) {
            throw new InvalidAuthError();
        }

        $account = Account::create(
            email_address: $data->email_address,
            password: $encrypted_password,
            host: $data->host,
            port: $data->port,
            token: UUID::v4(),
            type: AccountType::SENDER, // dps corrigir
            username: $data->username
        );

        $this->accountRepository->save($account);

        $output = new RegisterAccountOutputData(
            account: $account
        );

        return $output;
    }
}
