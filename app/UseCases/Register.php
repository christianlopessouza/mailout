<?php

namespace App\UseCases;

use App\Data\Input\EmailAuthenticationInputData;
use App\Data\Input\RegisterInputData;
use App\Data\Output\RegisterOutputData;
use App\Domain\Entities\Account;
use App\Errors\AccountAlreadyRegisteredError;
use App\Errors\InvalidAuthError;
use App\Errors\PasswordDoesntMatchError;
use App\Errors\UnauthorizedDomainError;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Services\EmailAuthenticationService;
use App\Util\UUID;

class Register
{
    public function __construct(
        public readonly ClientRepository $clientRepository,
        public readonly AccountRepository $accountRepository,
        public readonly EmailAuthenticationService $emailAuthenticationService
    ) {
    }

    public function execute(RegisterInputData $input): RegisterOutputData
    {
        [
            $email,
            $password,
            $password_confirmation,
            $host,
            $port
        ] = [$input->email, $input->password, $input->password_confirmation, $input->host, $input->port];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new InvalidAuthError('Email is not valid');

        if ($password !== $password_confirmation)
            throw new PasswordDoesntMatchError();

        $password = Crypto::encrypt($password);

        $domain = substr(strrchr($email, '@'), 1);
        $domainValid = $this->clientRepository->findByDomain($domain);
        if (!$domainValid)
            throw new UnauthorizedDomainError();

        $alreadyRegistered = $this->accountRepository->findByEmail($email);
        if ($alreadyRegistered)
            throw new AccountAlreadyRegisteredError();

        $authentication = $this->emailAuthenticationService->authenticate(
            EmailAuthenticationInputData::validateAndCreate([
                'credentials' => [
                    'email_address' => $email,
                    'password' => $password,
                    'host' => $host,
                    'port' => $port,
                    'username' => null
                ]
            ])
        );

        if (!$authentication)
            throw new InvalidAuthError();

        $account = Account::create(
            email_address: $email,
            password: $password,
            host: $host,
            port: $port,
            token: UUID::v4(),
        );

        $this->accountRepository->save($account);

        $output = new RegisterOutputData(
            account: $account
        );

        return $output;
    }
}
