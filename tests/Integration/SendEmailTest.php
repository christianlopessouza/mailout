<?php

use App\Errors\EmailSendFailureError;
use App\Helper\Crypto;
use App\Infrastructure\Services\EmailSenderService;
use Tests\TestCase;
use App\UseCases\SendEmail;
use App\Domain\Entities\Folder;
use App\Domain\Entities\Account;
use Illuminate\Support\Facades\DB;
use App\Data\Input\SendEmailInputData;
use App\Domain\Entities\Attachment;
use App\Domain\Entities\Email;
use App\Domain\Enums\Folder as FolderType;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Util\UUID;
use App\UseCases\Services\SendEmailService;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Services\AttachmentService;
use App\Infrastructure\Persistence\AttachmentRepository;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Infrastructure\Persistence\Facades\FacadesAttachmentRepository;
use App\Infrastructure\Services\S3AttachmentService;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementTemplateRepository;
use Aws\S3\S3Client;
uses(TestCase::class);

class sendEmailContainer
{
    public readonly EmailComplementService $emailComplementService;
    public readonly EmailComplementTemplateRepository $emailComplementTemplateRepository;
    public readonly AttachmentRepository $attachmentRepository;
    public readonly AttachmentService $attachmentService;
    public readonly EmailComplementRepository $emailComplementRepository;
    public readonly SendEmailService $sendEmailService;
    public  $emailSenderService;
    public readonly S3Client $s3Client;
    public readonly EmailRepository $emailRepository;
    public readonly FolderRepository $folderRepository;
    public readonly AccountRepository $accountRepository;
    public readonly SendEmail $sendEmail;
    public function __construct()
    {
        $this->s3Client = Mockery::mock(S3Client::class);
        $this->emailSenderService = Mockery::mock(EmailSenderService::class);
        $this->attachmentService = new S3AttachmentService($this->s3Client);
        $this->emailSenderService->shouldReceive('send')
            ->andReturn(true) 
            ->getMock();
        $this->folderRepository = new FacadesFolderRepository();
        $this->emailRepository = new FacadesEmailRepository();
        $this->attachmentRepository = new FacadesAttachmentRepository();
        $this->emailComplementRepository = new FacadesEmailComplementRepository();
        $this->emailComplementTemplateRepository = new FacadesEmailComplementTemplateRepository();
        $this->emailComplementService = new EmailComplementService($this->emailComplementTemplateRepository);
        $this->accountRepository = new FacadesAccountRepository();
        $this->sendEmailService = new SendEmailService(
            folderRepository: $this->folderRepository,
            emailSenderService: $this->emailSenderService, 
            emailRepository: $this->emailRepository,
            emailComplementService: $this->emailComplementService, 
            emailComplementRepository: $this->emailComplementRepository, 
            attachmentService: $this->attachmentService, 
            attachmentRepository: $this->attachmentRepository,
            emailComplementTemplateRepository: $this->emailComplementTemplateRepository 
        );
        
        $this->sendEmail = new SendEmail(
            sendEmailService: $this->sendEmailService,
        );
    }
}


describe('Send email', function () {
    beforeEach(function () {
        DB::table('accounts')->delete();
        DB::table('folders')->delete();
        DB::table('emails')->delete();
        DB::table('email_complements_template')->delete();
        DB::table('email_complements')->delete();

        $container = new sendEmailContainer();
        $this->account = Account::create('root@gruposuper.com.br', Crypto::encrypt('Xox,s~Z.W{}O'), 'mail.gruposuper.com.br', 587, UUID::v4());
        $container->accountRepository->save($this->account);

        $container->folderRepository->save(Folder::create(
            slug: FolderType::SENT->value,
            name: FolderType::SENT->value
        ));
        $this->emailRepository = $container->emailRepository;
        $this->emailSenderService = $container->emailSenderService;
        $this->folderRepository = $container->folderRepository;
        $this->sendEmail = $container->sendEmail;
        $this->email_id = UUID::v7(); 
        $this->input = SendEmailInputData::validateAndCreate([
            'account' => $this->account,
            'email' => [
                'email_id' => $this->email_id,
                'to' => ['email@teste.com'],
                'cc' => [],
                'bcc' => [],
                'subject' => 'Subject',
                'body' => 'Body',
                'origin' => 'manual',
                'thread_id' => UUID::v4(),
                'attachments' => [],
                'reply_to' => null,
                'transactional' => false,
                'complements' => (object)[
                    'value' => 'string'
                ]

                
            ]
        ]);

        $this->sendEmailService = $container->sendEmailService;

        DB::table('email_complements_template')->insert([
            'id' => UUID::v7(),
            'client_id' => $this->account->getId(),
            'template' => json_encode([
                'value' => ['string', 'bool', 'array'] 
            ])
        ]);

         DB::table('email_complements')->insert([
            'email_id' => $this->email_id,  
            'complement_data' => '{"value": "string"}',  
            'created_at' => now(),
            'updated_at' => now()
        ]);
    });

    it('should send email', function () {
        $this->input = SendEmailInputData::validateAndCreate([
            'account' => $this->account,
            'email' => [
                'email_id' => $this->email_id,
                'to' => ['email@teste.com'],
                'cc' => [],
                'bcc' => [],
                'subject' => 'Subject',
                'body' => 'Body',
                'origin' => 'manual',
                'thread_id' => UUID::v4(),
                'attachments' => [],
                'reply_to' => null,
                'transactional' => false,
                'complements' => null
            ]
        ]);

        $result = $this->sendEmail->execute($this->input);
        expect($result->email->getId())->toBeString();
    });

    it('should send email - only `to`', function () {
         $this->input = SendEmailInputData::validateAndCreate([
            'account' => $this->account,
            'email' => [
                'email_id' => $this->email_id,
                'to' => ['email@teste.com'],
                'cc' => [],
                'bcc' => [],
                'subject' => 'Subject',
                'body' => 'Body',
                'origin' => 'manual',
                'thread_id' => UUID::v4(),
                'attachments' => [],
                'reply_to' => null,
                'transactional' => false,
                'complements' => null
            ]
        ]);
        $result = $this->sendEmail->execute($this->input);
        expect($result->email->getId())->toBeString();
    });

     it('should not send email - failed', function () {
        $container = new sendEmailContainer();

        $this->input->email->complements = null;
        $emailSenderService = Mockery::mock(EmailSenderService::class);
        $emailSenderService->shouldReceive('send')
            ->andReturn(false)
            ->getMock();

        $sendEmailService = new SendEmailService(
            folderRepository: $container->folderRepository,
            emailSenderService: $emailSenderService, 
            emailRepository: $container->emailRepository,
            emailComplementService: $container->emailComplementService, 
            emailComplementRepository: $container->emailComplementRepository, 
            attachmentService: $container->attachmentService, 
            attachmentRepository: $container->attachmentRepository,
            emailComplementTemplateRepository: $container->emailComplementTemplateRepository
        );

        $sendMail = new SendEmail(
             $sendEmailService
        );

        $this->expectException(EmailSendFailureError::class);
        $sendMail->execute($this->input);
    });

    it('should create and validate template and complements', function () {
        $template = DB::table('email_complements_template')
            ->where('client_id', $this->account->getId())
            ->first();

        $this->assertNotNull($template, 'Template should exist in the database.');
        $template = json_decode($template->template);

        $complementValue = $this->input->email->complements->value;

        $this->assertTrue(in_array($complementValue, $template->value), 'Complement value is not valid.');
    });
});
