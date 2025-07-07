<?php

use App\Data\Input\StoreEmailQueueInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Client;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Folder as FolderType;
use App\Errors\ClientNotFoundError;
use App\Errors\EmailQueueEmptyError;
use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailQueueRepository;
use App\Infrastructure\Persistence\Facades\FacadesFlagRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailQueueRepository;
use App\Infrastructure\Persistence\FlagRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\UseCases\StoreEmailQueue;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

class StoreEmailQueueContainer
{
    public readonly FolderRepository $folderRepository;
    public readonly ClientRepository $clientRepository;
    public readonly AccountRepository $accountRepository;
    public readonly EmailQueueRepository $emailQueueRepository;
    public readonly FlagRepository $flagRepository;
    public readonly StoreEmailQueue $storeEmailQueue;

    public function __construct()
    {
        $this->folderRepository = new FacadesFolderRepository();
        $this->clientRepository = new FacadesClientRepository();
        $this->accountRepository = new FacadesAccountRepository();
        $this->emailQueueRepository = new FacadesEmailQueueRepository();
        $this->flagRepository = new FacadesFlagRepository();

        $this->storeEmailQueue = new StoreEmailQueue(
            folderRepository: $this->folderRepository,
            clientRepository: $this->clientRepository,
            emailQueueRepository: $this->emailQueueRepository,
            flagRepository: $this->flagRepository
        );
    }
}

describe('Database: Store email queue', function () {
    beforeEach(function () {
        // Limpa todas as tabelas envolvidas
        DB::table('email_queue')->delete();
        DB::table('folders')->delete();
        DB::table('accounts')->delete();
        DB::table('clients')->delete();
        DB::table('flags')->delete();

        // Monta o container com as Facades reais
        $container = new StoreEmailQueueContainer();
        $this->folderRepository = $container->folderRepository;
        $this->clientRepository = $container->clientRepository;
        $this->accountRepository = $container->accountRepository;
        $this->emailQueueRepository = $container->emailQueueRepository;
        $this->flagRepository = $container->flagRepository;
        $this->storeEmailQueue = $container->storeEmailQueue;

        // Cria e salva uma conta real no banco
        $this->account = Account::create(
            email_address: 'mail@example.com',
            password: 'password',
            host: 'mail.gruposuper.com.br',
            port: 587,
            token: UUID::v4()
        );
        $this->accountRepository->save($this->account);

        // Cria e salva um cliente real no banco
        $this->client = Client::create(
            name: 'testClient',
            token: UUID::v4(),
            domain: 'example.com'
        );
        $this->clientRepository->save($this->client);

        // Cria e salva uma pasta “sent” no banco
        $sentFolder = Folder::create(
            slug: FolderType::SENT->value,
            name: FolderType::SENT->value
        );
        $this->folderRepository->save($sentFolder);

        // Monta o array de input com duas mensagens
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
                    'external_id' => null,
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
                    'external_id' => null,
                ],
            ],
        ]);
    });

    it('should store email in mailqueue', function () {
        $result = $this->storeEmailQueue->execute($this->input);
        expect($result->emails)->toBeArray();
    });

    it('should store email with external_id', function () {
        // Define external_id para ambas as mensagens
        $this->input->emails[0]['external_id'] = uniqid();
        $this->input->emails[1]['external_id'] = uniqid();

        $result = $this->storeEmailQueue->execute($this->input);

        expect($result->emails[0]['external_id'])->toBeString();
        expect($result->emails[1]['external_id'])->toBeString();
    });

    it('should store one email with external_id and another without', function () {
        $this->input->emails[0]['external_id'] = uniqid();

        $result = $this->storeEmailQueue->execute($this->input);

        expect($result->emails[0]['external_id'])->toBeString();
        expect($result->emails[1]['external_id'])->toBeNull();
    });

    it('shouldn\'t store email in mailqueue if client not found', function () {
        $this->input->client_id = '00000000-0000-0000-0000-000000000000';
        $this->expectException(ClientNotFoundError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shouldn\'t store email in mailqueue if the list of email is empty', function () {
        $this->input->emails = [];
        $this->expectException(EmailQueueEmptyError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shouldn\'t store email in mailqueue if client hasn\'t authorization to some account', function () {
        $this->input->emails[0]['from'] = 'mail@anotherdomain.com';
        $this->expectException(UnauthorizedDomainError::class);
        $this->storeEmailQueue->execute($this->input);
    });

    it('shouldn\'t store email in mailqueue if insert query fails', function () {
        // Cria um mock do EmailQueueRepository que retorna false em saveAll()
        $mockEmailQueueRepo = Mockery::mock(EmailQueueRepository::class);
        $mockEmailQueueRepo
            ->shouldReceive('saveAll')
            ->andReturn(false)
            ->getMock();

        // Mantém as Facades reais para os outros repositórios
        $container = new StoreEmailQueueContainer();
        $folderRepo = $container->folderRepository;
        $clientRepo = $container->clientRepository;
        $flagRepo = $container->flagRepository;

        $storeEmailQueueWithFail = new StoreEmailQueue(
            folderRepository: $folderRepo,
            clientRepository: $clientRepo,
            emailQueueRepository: $mockEmailQueueRepo,
            flagRepository: $flagRepo
        );

        $this->expectException(\Exception::class);
        $storeEmailQueueWithFail->execute($this->input);
    });
});
