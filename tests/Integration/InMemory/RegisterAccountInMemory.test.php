<?php

use App\Data\Input\RegisterInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Client;
use App\Errors\AccountAlreadyRegisteredError;
use App\Errors\InvalidAuthError;
use App\Errors\PasswordDoesntMatchError;
use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryClientRepository;
use App\Infrastructure\Services\EmailAuthenticationService;
use App\UseCases\Register;
use App\Util\UUID;
use Tests\TestCase;

uses(TestCase::class);

class registerContainerInMemory
{
    public readonly AccountRepository $accountRepository;
    public readonly ClientRepository $clientRepository;
    public readonly EmailAuthenticationService $emailAuthenticationService;
    public function __construct()
    {
        $this->emailAuthenticationService = Mockery::mock(EmailAuthenticationService::class);
        $this->emailAuthenticationService->shouldReceive('authenticate')
            ->andReturn(true)
            ->getMock();

        $this->accountRepository = new InMemoryAccountRepository();
        $this->clientRepository = new InMemoryClientRepository();
    }
}

describe('InMemory:Register Account', function () {
    beforeEach(function () {
        $container = new registerContainerInMemory();
        $this->accountRepository = $container->accountRepository;
        $this->clientRepository = $container->clientRepository;
        $this->emailAuthenticationService = $container->emailAuthenticationService;

        $this->register = new Register(
            clientRepository: $this->clientRepository,
            accountRepository: $this->accountRepository,
            emailAuthenticationService: $this->emailAuthenticationService
        );


        $this->clientRepository->save(Client::create(
            name: 'Test Client',
            token: 'token',
            domain: 'example.com',
        ));

        $this->input = (object)[
            'email' => 'my@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'host' => 'smtp.example.com',
            'port' => 587,
        ];
    });

    it('should register account', function () {
        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $result = $this->register->execute($input);
        expect($result->account->getId())->toBeString();
    });


    it('should not register account - already registered', function () {
        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $this->accountRepository->save(
            Account::create(
                email_address: $this->input->email,
                password: $this->input->password,
                host: $this->input->host,
                port: $this->input->port,
                token: UUID::v4(),

            )
        );

        $this->expectException(AccountAlreadyRegisteredError::class);
        $this->register->execute($input);
    });
    it('should not register account - invalid email', function () {
        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $this->accountRepository->save(
            Account::create(
                email_address: $this->input->email,
                password: $this->input->password,
                host: $this->input->host,
                port: $this->input->port,
                token: UUID::v4(),
            )
        );

        $this->expectException(AccountAlreadyRegisteredError::class);
        $this->register->execute($input);
    });

    it('should not register account - invalid auth', function () {

        $this->emailAuthenticationService = Mockery::mock(EmailAuthenticationService::class);
        $this->emailAuthenticationService->shouldReceive('authenticate')
            ->andReturn(false)
            ->getMock();

        $this->register = new Register(
            clientRepository: $this->clientRepository,
            accountRepository: $this->accountRepository,
            emailAuthenticationService: $this->emailAuthenticationService,
        );

        $this->input->password = $this->input->password_confirmation = 'invalid_password';

        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $this->expectException(InvalidAuthError::class);

        $this->register->execute($input);
    });

    it('should not register account - domain not authorized', function () {
        $this->input->email = 'my@anotherdomain.com';

        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $this->expectException(UnauthorizedDomainError::class);

        $this->register->execute($input);
    });

    it('should not register account - passwords does not match', function () {
        $this->input->password = '12345';
        $this->input->password_confirmation = '7890';

        $input = RegisterInputData::validateAndCreate((array)$this->input);

        $this->expectException(PasswordDoesntMatchError::class);

        $this->register->execute($input);
    });
});
