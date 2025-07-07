<?php

use App\Data\Input\ListEmailsByClientInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Client;
use App\Domain\Entities\Email;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Errors\ClientNotFoundError;
use App\Errors\UnauthorizedError;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\UseCases\ListEmailsByClient;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

class ListEmailsByClientDatabaseContainer
{
    public readonly FolderRepository $folderRepository;
    public readonly ClientRepository $clientRepository;
    public readonly AccountRepository $accountRepository;
    public readonly EmailRepository $emailRepository;
    public readonly ListEmailsByClient $listEmails;

    public function __construct()
    {
        $this->folderRepository = new FacadesFolderRepository();
        $this->clientRepository = new FacadesClientRepository();
        $this->accountRepository = new FacadesAccountRepository();
        $this->emailRepository = new FacadesEmailRepository();

        $this->listEmails = new ListEmailsByClient(
            emailRepository: $this->emailRepository,
            folderRepository: $this->folderRepository,
            clientRepository: $this->clientRepository,
            accountRepository: $this->accountRepository
        );
    }
}

describe('Database: List emails by client', function () {
    beforeEach(function () {
        // Roda migrations para garantir esquema atualizado
        Artisan::call('migrate:fresh');

        // Limpa tabelas
        DB::table('emails')->delete();
        DB::table('folders')->delete();
        DB::table('accounts')->delete();
        DB::table('clients')->delete();

        // Monta container com repositórios reais
        $container = new ListEmailsByClientDatabaseContainer();
        $this->folderRepository = $container->folderRepository;
        $this->clientRepository = $container->clientRepository;
        $this->accountRepository = $container->accountRepository;
        $this->emailRepository = $container->emailRepository;
        $this->listEmails = $container->listEmails;

        // Cria e salva dois clientes
        $this->client[0] = Client::create(
            name: 'testClient',
            token: UUID::v4(),
            domain: 'example.com'
        );
        $this->clientRepository->save($this->client[0]);

        $this->client[1] = Client::create(
            name: 'testClient2',
            token: UUID::v4(),
            domain: 'test.com'
        );
        $this->clientRepository->save($this->client[1]);

        // Cria e salva três contas (duas para client[0] e uma para client[1])
        $this->account[0] = Account::create(
            email_address: 'mail@example.com',
            password: 'password',
            host: 'localhost',
            port: 25,
            token: UUID::v4()
        );
        $this->accountRepository->save($this->account[0]);

        $this->account[1] = Account::create(
            email_address: 'mail2@example.com',
            password: 'password',
            host: 'localhost',
            port: 25,
            token: UUID::v4()
        );
        $this->accountRepository->save($this->account[1]);

        $this->account[2] = Account::create(
            email_address: 'mail@test.com',
            password: 'password',
            host: 'localhost',
            port: 25,
            token: UUID::v4()
        );
        $this->accountRepository->save($this->account[2]);

        // Cria e salva duas pastas (Inbox e Sent)
        $this->folder['inbox'] = Folder::create(
            slug: 'inbox',
            name: 'Inbox'
        );
        $this->folderRepository->save($this->folder['inbox']);

        $this->folder['sent'] = Folder::create(
            slug: 'sent',
            name: 'Sent'
        );
        $this->folderRepository->save($this->folder['sent']);

        // Insere quatro e-mails distintos
        // 1) Outgoing da account[0], to=['third@example.com']
        $this->emailRepository->save(
            Email::create(
                account_id: $this->account[0]->getId(),
                from: $this->account[0]->getEmailAddress(),
                to: ['third@example.com'],
                subject: 'SUBJECT',
                body: 'BODY',
                folder_id: $this->folder['sent']->getId(),
                direction: Direction::OUTGOING,
                origin: Origin::MANUAL,
                read: null,
                processed_at: new \DateTime('2023-02-17 20:00:00')
            )
        );

        // 2) Outgoing da account[0], to=['second@example.com','first@example.com','mail@outgoing.com']
        $this->emailRepository->save(
            Email::create(
                account_id: $this->account[0]->getId(),
                from: $this->account[0]->getEmailAddress(),
                to: ['second@example.com', 'first@example.com', 'mail@outgoing.com'],
                subject: 'ANOTHER SUBJECT',
                body: 'MY BODY',
                folder_id: $this->folder['sent']->getId(),
                direction: Direction::OUTGOING,
                origin: Origin::MANUAL,
                read: null,
                processed_at: new \DateTime('2022-05-10 10:00:00')
            )
        );

        // 3) Outgoing da account[1], to=['mail2@outgoing.com']
        $this->emailRepository->save(
            Email::create(
                account_id: $this->account[1]->getId(),
                from: $this->account[1]->getEmailAddress(),
                to: ['mail2@outgoing.com'],
                subject: 'THIRD SUBJECT',
                body: 'BODY 2',
                folder_id: $this->folder['sent']->getId(),
                direction: Direction::OUTGOING,
                origin: Origin::MANUAL,
                read: null,
                processed_at: new \DateTime('2024-07-11 12:00:00')
            )
        );

        // 4) Incoming para account[0], from='from@destiny.com', to=['mail@example.com']
        $this->emailRepository->save(
            Email::create(
                account_id: $this->account[0]->getId(),
                from: 'from@destiny.com',
                to: ['mail@example.com'],
                subject: 'FOURTH SUBJECT',
                body: 'Hello World',
                folder_id: $this->folder['inbox']->getId(),
                direction: Direction::INCOMING,
                read: false,
                processed_at: new \DateTime('2025-11-13 13:00:00')
            )
        );
    });

    it('should list emails by client accounts', function () {
        $input = ListEmailsByClientInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['first@example.com', 'second@example.com', 'from@destiny.com']
            ],
            'client' => $this->client[0]
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(2);
    });

    it('should list emails by client with account restriction', function () {
        $input = ListEmailsByClientInputData::validateAndCreate([
            'filter' => [
                'accounts' => [$this->account[0]->getId()],
                'folder_id' => $this->folder['sent']->getId()
            ],
            'client' => $this->client[0]
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(2);
    });

    /*    it('shouldnt list emails - client not found', function () {
            $fakeClient = Client::create(
                name: 'nonexistent',
                token: UUID::v4(),
                domain: 'doesnotexist.com'
            );

            $input = ListEmailsByClientInputData::validateAndCreate([
                'filter' => [
                    'accounts' => [$this->account[1]->getId()],
                    'query_email_address' => ['never-sent@example.com'],
                ],
                'client' => $fakeClient
            ]);

            $this->expectException(UnauthorizedError::class);
            $this->listEmails->execute($input);
        });
    */
    it('shouldnt list emails - client accessing unauthorized account', function () {
        $input = ListEmailsByClientInputData::validateAndCreate([
            'filter' => [
                'accounts' => [$this->account[0]->getId(), $this->account[2]->getId()]
            ],
            'client' => $this->client[1]
        ]);

        $this->expectException(UnauthorizedError::class);
        $this->listEmails->execute($input);
    });
});
