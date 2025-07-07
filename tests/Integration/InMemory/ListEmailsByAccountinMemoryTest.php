<?php


use App\Data\Input\ListEmailsByAccountInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Email;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Errors\FilteREmailError;
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryEmailRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryFolderRepository;
use Tests\TestCase;
use App\Errors\InvalidDateRangeError;
use App\Errors\UnauthorizedError;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\UseCases\ListEmails;
use App\UseCases\ListEmailsByAccount;
use App\Util\UUID;

uses(TestCase::class);

class listEmailsAccountContainerInMemory
{
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly EmailRepository $emailRepository;
    public readonly ListEmails $listEmails;
    public function __construct()
    {
        $this->folderRepository = new InMemoryFolderRepository();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->emailRepository = new InMemoryEmailRepository();
        $this->listEmails = new ListEmailsByAccount($this->emailRepository, $this->folderRepository, $this->accountRepository);
    }
}

describe('InMemory:List emails', function () {
    beforeEach(function () {
        $container = new listEmailsAccountContainerInMemory();
        $this->listEmails = $container->listEmails;

        $this->account = Account::create('mail@example.com', 'password', 'localhost', 25, UUID::v4());
        $container->accountRepository->save($this->account);

        $this->folder['inbox'] = Folder::create('inbox', 'Inbox');
        $container->folderRepository->save($this->folder['inbox']);

        $this->folder['sent'] = Folder::create('sent', 'Sent');
        $container->folderRepository->save($this->folder['sent']);

        $this->folderRepository = $container->folderRepository;

        $container->emailRepository->save(
            Email::create(
                account_id: $this->account->getId(),
                from: 'first@example.com',
                to: [$this->account->getEmailAddress(), 'third@example.com'],
                subject: 'SUBJECT',
                body: 'BODY',
                folder_id: $this->folder['inbox']->getId(),
                direction: Direction::INCOMING,
                read: true,
                read_at: new \DateTime('2023-02-17 20:00:00'),
                processed_at: new \DateTime('2023-02-17 20:00:00')
            )
        );
        $container->emailRepository->save(
            Email::create(
                account_id: $this->account->getId(),
                from: $this->account->getEmailAddress(),
                to: ['second@example.com', 'first@example.com'],
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
                account_id: $this->account->getId(),
                from: $this->account->getEmailAddress(),
                to: ['third@example.com'],
                subject: 'THIRD SUBJECT',
                body: 'BODY 2',
                folder_id: $this->folder['sent']->getId(),
                direction: Direction::OUTGOING,
                origin: Origin::MANUAL,
                read: null,
                processed_at: new \DateTime('2024-07-11 12:00:00')
            )
        );
    });


    it('should list emails by folder', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'folder_id' => $this->folder['sent']->getId()
            ],
            'account' => $this->account
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(2);
    });

    it('should list emails by query_email_address', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['first@example.com', 'third@example.com']
            ],
            'account' => $this->account
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(3);
    });

    it('shoudnt list emails if date ranges are invalid', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['first@example.com'],
                'read_start_date' => new DateTime('2025-02-01'),
                'read_end_date' => new DateTime('2022-01-01')
            ],
            'account' => $this->account
        ]);

        $this->expectException(InvalidDateRangeError::class);

        $this->listEmails->execute($input);
    });

    it('shouldnt list emails - filter doesnt match', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                "query_email_address" => ['never-mail@example.com'],
            ],
            'account' => $this->account
        ]);
        $result = $this->listEmails->execute($input);
        expect($result)->toHaveCount(0);
    });
});
