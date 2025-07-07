<?php

use App\Infrastructure\Persistence\InMemory\InMemoryFolderRepository;
use Tests\TestCase;
use App\Domain\Entities\Folder;
use App\Domain\Entities\Account;
use App\Data\Input\StoreEmailQueueInputData;
use App\Domain\Entities\Client;
use App\Domain\Enums\Folder as FolderType;
use App\Errors\ClientNotFoundError;
use App\Errors\EmailQueueEmptyError;
use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailQueueRepository;
use App\Infrastructure\Persistence\FlagRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryClientRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryEmailQueueRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryFlagRepository;
use App\UseCases\StoreEmailQueue;
use App\Util\UUID;
use Illuminate\Contracts\Cache\Store;

uses(TestCase::class);

class storeEmailQueueContainerInMemory
{
    public readonly EmailQueueRepository $emailQueueRepository;
    public readonly ClientRepository $clientRepository;
    public readonly StoreEmailQueue $storeEmailQueue;
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly FlagRepository $flagRepository;

    public function __construct()
    {
        $this->emailQueueRepository = new InMemoryEmailQueueRepository();
        $this->clientRepository = new InMemoryClientRepository();
        $this->folderRepository = new InMemoryFolderRepository();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->flagRepository = new InMemoryFlagRepository();
        $this->storeEmailQueue = new StoreEmailQueue(
            $this->folderRepository,
            $this->clientRepository,
            $this->emailQueueRepository,
            $this->flagRepository
        );
    }
}

describe('InMemory:Store email queue', function () {
    beforeEach(function () {
        $container = new storeEmailQueueContainerInMemory();

        $this->account = Account::create('mail@example.com', 'password', 'mail.gruposuper.com.br', 587, UUID::v4());
        $container->accountRepository->save($this->account);

        $this->client = Client::create('testClient', uniqid(), 'example.com');
        $container->clientRepository->save($this->client);

        $container->folderRepository->save(Folder::create(
            slug: FolderType::SENT->value,
            name: FolderType::SENT->value
        ));

        $this->folderRepository = $container->folderRepository;
        $this->storeEmailQueue = $container->storeEmailQueue;
        $this->emailQueueRepository = $container->emailQueueRepository;
        $this->clientRepository = $container->clientRepository;
        $this->flagRepository = $container->flagRepository;

        $this->input = StoreEmailQueueInputData::validateAndCreate([
            'client_id' => $this->client->getId(),
            'emails' => [
                [
                    'from' => 'mail@example.com',
                    'to' => ['to@gruposuper.com.br'],
                    'cc' => ['cc@gruposuper.com.br'],
                    'bcc' => ['bcc@gruposuper.com.br'],
                    'subject' => 'This Subject',
                    'body' => 'This Body',
                    'thread_id' => uniqid(),
                    'attachments' => [],
                    'external_id' => null
                ],
                [
                    'from' => 'mail@example.com',
                    'to' => ['to2@gruposuper.com.br'],
                    'cc' => ['cc2@gruposuper.com.br'],
                    'bcc' => ['bcc2@gruposuper.com.br'],
                    'subject' => 'This Subject - 2',
                    'body' => 'This Body - 2',
                    'thread_id' => uniqid(),
                    'attachments' => [],
                    'external_id' => null
                ]
            ]
        ]);
    });

    it('should store email in mailqueue', function () {
        $result = $this->storeEmailQueue->execute($this->input);
        expect($result->emails)->toBeArray();
    });

    it('should store email with external_id', function () {
        $this->input->emails[0]['external_id'] = uniqid();
        $this->input->emails[1]['external_id'] = uniqid();

        $result = $this->storeEmailQueue->execute($this->input);
        expect($result->emails[0]['external_id'])->toBeString();
        expect($result->emails[1]['external_id'])->toBeString();
    });

    it('should store email with one with external_id and another without', function () {
        $this->input->emails[0]['external_id'] = uniqid();

        $result = $this->storeEmailQueue->execute($this->input);
        expect($result->emails[0]['external_id'])->toBestring();
        expect($result->emails[1]['external_id'])->toBeNull();
    });

    it('shouldnt store email in mailqueue if client not found', function () {
        $this->input->client_id = 'not-found';
        $this->expectException(ClientNotFoundError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shoundt store email in mailqueue if the list of email is empty', function () {
        $this->input->emails = [];
        $this->expectException(EmailQueueEmptyError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shoundt store email in mailqueue if client hasnt authorizantion to some account ', function () {
        $this->input->emails[0]['from'] = 'mail@anotherdomain.com';
        $this->expectException(UnauthorizedDomainError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shoundt store email in mailqueue if insert query fail', function () {
        $emailQueueRepository = Mockery::mock(EmailQueueRepository::class);

        $emailQueueRepository->shouldReceive('saveAll')
            ->andReturn(false)
            ->getMock();

        $storeEmailQueue = new StoreEmailQueue(
            $this->folderRepository,
            $this->clientRepository,
            $emailQueueRepository,
            $this->flagRepository
        );

        $this->expectException(Exception::class);
        $storeEmailQueue->execute($this->input);
    });
});
