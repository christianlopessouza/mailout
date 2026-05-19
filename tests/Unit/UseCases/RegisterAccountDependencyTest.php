<?php

use App\UseCases\RegisterAccount;
use App\Data\Input\RegisterAccountInputData;
use App\Domain\Contracts\IEmailAuthenticationService;
use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryClientRepository;
use App\Infrastructure\Services\RabbitMQService;
use Tests\TestCase;

uses(TestCase::class);

it('resolves account registration dependencies from the container', function () {
    expect(app(RegisterAccount::class))->toBeInstanceOf(RegisterAccount::class);
});

it('publishes an account sync event after registering an account', function () {
    $accountRepository = new InMemoryAccountRepository();
    $clientRepository = new InMemoryClientRepository();
    $clientRepository->save(Client::create(
        name: 'Test Client',
        token: 'client-token',
        domain: 'example.com',
    ));

    $emailAuthenticationService = Mockery::mock(IEmailAuthenticationService::class);
    $emailAuthenticationService
        ->shouldReceive('authenticate')
        ->once()
        ->andReturnTrue();

    $rabbitMQService = Mockery::mock(RabbitMQService::class);
    $rabbitMQService
        ->shouldReceive('publish')
        ->once()
        ->with('account_sync_queue', Mockery::on(function (array $payload) {
            return $payload['action'] === 'account_created'
                && is_string($payload['account_id'])
                && $payload['account_id'] !== '';
        }));

    $registerAccount = new RegisterAccount(
        accountRepository: $accountRepository,
        clientRepository: $clientRepository,
        emailAuthenticationService: $emailAuthenticationService,
        rabbitMQService: $rabbitMQService,
    );

    $output = $registerAccount->execute(RegisterAccountInputData::validateAndCreate([
        'email_address' => 'sender@example.com',
        'password' => 'secret',
        'host' => 'smtp.example.com',
        'port' => 587,
    ]));

    expect($accountRepository->findById($output->account->getId()))->not->toBeNull();
});
