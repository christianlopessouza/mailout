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
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryEmailRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryFolderRepository;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Tests\TestCase;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryClientRepository;
use App\UseCases\ListEmails;
use App\UseCases\ListEmailsByClient;
use App\Util\UUID;

uses(TestCase::class);

class listEmailsClientContainer
{
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly EmailRepository $emailRepository;
    public readonly ClientRepository $clientRepository;
    public readonly ListEmails $listEmails;
    public function __construct()
    {
        $this->folderRepository = new InMemoryFolderRepository();
        $this->clientRepository = new InMemoryClientRepository();
        $this->accountRepository = new InMemoryAccountRepository($this->clientRepository);
        $this->emailRepository = new InMemoryEmailRepository();
        $this->listEmails = new ListEmailsByClient($this->emailRepository, $this->folderRepository, $this->clientRepository, $this->accountRepository);
    }
}

describe('InMemory:List emails', function () {
    beforeEach(function () {
        $container = new listEmailsClientContainer();
        $this->listEmails = $container->listEmails;

        $this->client[0] = Client::create('testClient', uniqid(), 'example.com');
        $container->clientRepository->save($this->client[0]);

        $this->client[1] = Client::create('testClient2', uniqid(), 'test.com');
        $container->clientRepository->save($this->client[1]);

        $this->account[0] = Account::create('mail@example.com', 'password', 'localhost', 25, UUID::v4());
        $container->accountRepository->save($this->account[0]);

        $this->account[1] = Account::create('mail2@example.com', 'password', 'localhost', 25, UUID::v4());
        $container->accountRepository->save($this->account[1]);

        $this->account[2] = Account::create('mail@test.com', 'password', 'localhost', 25, UUID::v4());
        $container->accountRepository->save($this->account[2]);

        $this->folder['inbox'] = Folder::create('inbox', 'Inbox');
        $container->folderRepository->save($this->folder['inbox']);

        $this->folder['sent'] = Folder::create('sent', 'Sent');
        $container->folderRepository->save($this->folder['sent']);

        $this->folderRepository = $container->folderRepository;

        $container->emailRepository->save(
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
        $container->emailRepository->save(
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
        $container->emailRepository->save(
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
        $container->emailRepository->save(
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

    // it('should list emails by client with account restriction', function () {
    //     $input = ListEmailsByClientInputData::validateAndCreate([
    //         'filter' => [
    //             'accounts' => [$this->account[0]->getId()],
    //             'folder_id' => $this->folder['sent']->getId()
    //         ],
    //         'client_id' => $this->client[0]->getId()
    //     ]);

    //     $result = $this->listEmails->execute($input);

    //     expect($result)->toHaveCount(2);
    // });

    // it('shouldnt list emails - client not found', function () {

    //     $input = ListEmailsByClientInputData::validateAndCreate([
    //         'filter' => [
    //             "accounts" => [$this->account[1]->getId()],
    //             "query_email_address" => ['never-sent@example.com'],
    //         ],
    //         'client_id' => uniqid()

    //     ]);

    //     $this->expectException(ClientNotFoundError::class);

    //     $this->listEmails->execute($input);
    // });


    // it('shoudnt list emails - client accessing unauthorized account', function () {
    //     $input = ListEmailsByClientInputData::validateAndCreate([
    //         'filter' => [
    //             'accounts' => [$this->account[0]->getId(), $this->account[2]->getId()]
    //         ],
    //         'client_id' => $this->client[1]->getId()
    //     ]);

    //     $this->expectException(UnauthorizedError::class);

    //     $this->listEmails->execute($input);
    // });
});
