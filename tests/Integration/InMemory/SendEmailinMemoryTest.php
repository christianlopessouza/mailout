<?php

use App\Data\Input\SendEmailInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Folder;
use App\Errors\EmailSendFailureError;
use App\Infrastructure\Persistence\InMemory\InMemoryAccountRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryEmailRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryFolderRepository;
use App\Infrastructure\Services\EmailSenderService;
use App\Infrastructure\Services\EmailComplementService;
use App\UseCases\SendEmail;
use Tests\TestCase;
use App\Domain\Enums\Folder as FolderType;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Util\UUID;

uses(TestCase::class);

class sendEmailContainerInMemory
{
    public readonly EmailSenderService $emailSenderService;
    public readonly EmailRepository $emailRepository;
    public readonly EmailComplementService $emailComplementService;
    public readonly EmailComplementRepository $emailComplementRepository;
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly SendEmail $sendEmail;
    public function __construct()
    {
        $this->emailSenderService = Mockery::mock(EmailSenderService::class);
        $this->emailSenderService->shouldReceive('send')
            ->andReturn(true)
            ->getMock();

        $this->emailComplementService = Mockery::mock(EmailComplementService::class);
        $this->emailComplementService->shouldReceive('save')
            ->andReturn(true)
            ->getMock();

        $this->emailRepository = new InMemoryEmailRepository();
        $this->folderRepository = new InMemoryFolderRepository();
        $this->accountRepository = new InMemoryAccountRepository();
        $this->sendEmail = new SendEmail(
            emailSenderService: $this->emailSenderService,
            emailRepository: $this->emailRepository,
            folderRepository: $this->folderRepository,
            emailComplementService: $this->emailComplementService,
            emailComplementRepository: $this->emailComplementRepository
        );
    }
}

describe('InMemory:Send email', function () {
    beforeEach(function () {
        $container = new sendEmailContainerInMemory();
        $this->account = Account::create('mail@example.com', 'password', 'mail.gruposuper.com.br', 587, UUID::v4());
        $container->accountRepository->save($this->account);

        $container->folderRepository->save(Folder::create(
            slug: FolderType::SENT->value,
            name: FolderType::SENT->value
        ));

        $this->emailRepository = $container->emailRepository;
        $this->emailSenderService = $container->emailSenderService;
        $this->folderRepository = $container->folderRepository;
        $this->sendEmail = $container->sendEmail;

        $this->input = SendEmailInputData::validateAndCreate([
            'account' => $this->account,
            'email_data' => [
                'to' => ['destination@gmail.com'],
                'cc' => ['cc@example.com'],
                'bcc' => ['bcc@example.com'],
                'subject' => 'subject',
                'body' => 'body',
                'origin' => 'manual',
                'thread_id' => UUID::v4()
            ]
        ]);
    });

    it('should send email', function () {
        $result = $this->sendEmail->execute($this->input);
        expect($result->email->getId())->toBeString();
    });

    it('should send email - only `to`', function () {
        $this->input->email_data->cc = [];
        $this->input->email_data->bcc = [];
        $result = $this->sendEmail->execute($this->input);
        expect($result->email->getId())->toBeString();
    });

    it('should not send email - failed', function () {
        $emailSenderService = Mockery::mock(EmailSenderService::class);

        $emailSenderService->shouldReceive('send')
            ->andReturn(false)
            ->getMock();

        $sendMail = new SendEmail(
            emailSenderService: $emailSenderService,
            emailRepository: $this->emailRepository,
            folderRepository: $this->folderRepository
        );

        $this->expectException(EmailSendFailureError::class);

        $sendMail->execute($this->input);
    });
});
