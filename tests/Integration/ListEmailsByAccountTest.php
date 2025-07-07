<?php

use App\Data\Input\ListEmailsByAccountInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Email;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Errors\FilteREmailError;
use App\Errors\InvalidDateRangeError;
use App\Errors\UnauthorizedError;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\UseCases\ListEmailsByAccount;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

class ListEmailsAccountContainer
{
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly EmailRepository $emailRepository;
    public readonly ListEmailsByAccount $listEmails;

    public function __construct()
    {
        $this->folderRepository = new FacadesFolderRepository();
        $this->accountRepository = new FacadesAccountRepository();
        $this->emailRepository = new FacadesEmailRepository();

        $this->listEmails = new ListEmailsByAccount(
            emailRepository: $this->emailRepository,
            folderRepository: $this->folderRepository,
            accountRepository: $this->accountRepository
        );
    }
}

describe('Database: List emails by account', function () {
    beforeEach(function () {
        DB::table('emails')->delete();
        DB::table('folders')->delete();
        DB::table('accounts')->delete();

        $container = new ListEmailsAccountContainer();
        $this->listEmails = $container->listEmails;
        $this->accountRepository = $container->accountRepository;
        $this->folderRepository = $container->folderRepository;
        $this->emailRepository = $container->emailRepository;

        $this->account = Account::create(
            email_address: 'mail@example.com',
            password: 'password',
            host: 'localhost',
            port: 25,
            token: UUID::v4(),
        );
        $this->accountRepository->save($this->account);

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

        $email1 = Email::create(
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
        );
        $this->emailRepository->save($email1);

        $email2 = Email::create(
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
        );
        $this->emailRepository->save($email2);

        $email3 = Email::create(
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
        );
        $this->emailRepository->save($email3);
    });

    it('should list emails by folder', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'folder_id' => $this->folder['sent']->getId(),
            ],
            'account' => $this->account,
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(2);
    });

    it('should list emails by query_email_address', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['first@example.com', 'third@example.com'],
            ],
            'account' => $this->account,
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(3);
    });

    it('shouldn\'t list emails if date ranges are invalid', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['first@example.com'],
                'read_start_date' => new \DateTime('2025-02-01'),
                'read_end_date' => new \DateTime('2022-01-01'),
            ],
            'account' => $this->account,
        ]);

        $this->expectException(InvalidDateRangeError::class);

        $this->listEmails->execute($input);
    });

    it('shouldn\'t list emails - filter doesn\'t match', function () {
        $input = ListEmailsByAccountInputData::validateAndCreate([
            'filter' => [
                'query_email_address' => ['never-mail@example.com'],
            ],
            'account' => $this->account,
        ]);

        $result = $this->listEmails->execute($input);

        expect($result)->toHaveCount(0);
    });
});
