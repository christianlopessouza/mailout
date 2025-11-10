<?php

use App\Data\EmailFilterData;
use App\Data\Input\FilterEmailsByClientInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Client;
use App\Domain\Entities\Email;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\UseCases\FilterEmailsByClient;
use App\UseCases\Services\EmailFiltersService;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

describe('Filter Emails By Client', function () {
    beforeEach(function () {
        DB::table('emails')->delete();
        DB::table('email_search_tokens')->delete();
        DB::table('clients')->delete();
        DB::table('accounts')->delete();
        DB::table('folders')->delete();
        DB::table('email_complements')->delete();

        $this->clientRepository = new FacadesClientRepository();
        $this->accountRepository = new FacadesAccountRepository();
        $this->emailRepository = new FacadesEmailRepository();
        $this->folderRepository = new FacadesFolderRepository();

        $this->client = Client::create(
            name: 'Test Client',
            domain: 'testclient.com',
            token: UUID::v4()
        );

        $this->clientRepository->save($this->client);

        // Create another client for testing
        $this->otherClient = Client::create(
            name: 'Other Client',
            domain: 'differentclient.com',
            token: UUID::v4()
        );

        $this->clientRepository->save($this->otherClient);

        // Create accounts for the test client
        $this->account1 = Account::create(
            email_address: 'foo@testclient.com',
            password: Crypto::encrypt('password'),
            host: 'email-smtp.us-east-1.amazonaws.com',
            port: 587,
            token: null
        );

        $this->account2 = Account::create(
            email_address: 'bar@testclient.com',
            password: Crypto::encrypt('password2'),
            host: 'email-smtp.us-east-1.amazonaws.com',
            port: 587,
            token: null
        );

        // Create account for different client
        $this->account3 = Account::create(
            email_address: 'test@differentclient.com',
            password: Crypto::encrypt('password3'),
            host: 'email-smtp.us-east-1.amazonaws.com',
            port: 587,
            token: null
        );

        $this->accountRepository->save($this->account1);
        $this->accountRepository->save($this->account2);
        $this->accountRepository->save($this->account3);

        // Create folders for the accounts
        $folder1 = Folder::create(
            slug: 'ClientTesting',
            name: 'Client Testing',
            account_id: $this->account1->getId(),
        );

        $this->folderRepository->save($folder1);

        $folder2 = Folder::create(
            slug: 'none',
            name: 'None',
            account_id: $this->account1->getId(),
        );

        $this->folderRepository->save($folder2);

        $folder3 = Folder::create(
            slug: 'inbox',
            name: 'Inbox',
            account_id: $this->account2->getId(),
        );

        $this->folderRepository->save($folder3);

        $this->folder1 = $folder1;
        $this->folder2 = $folder2;
        $this->folder3 = $folder3;

        // Create test emails for the test client accounts
        $email1 = Email::create(
            account_id: $this->account1->getId(),
            from: 'foo@testclient.com',
            to: ['ti.superest@gmail.com'],
            cc: ['testing@testclient.com'],
            bcc: ['gnitset@testclient.com'],
            subject: 'Test Email 1',
            body: 'This is a test email for filtering by client.',
            direction: Direction::OUTGOING,
            folder_id: $this->folder1->getId(),
            attachments: false,
            origin: Origin::MANUAL,
            processed_at: new \DateTime()
        );

        $email2 = Email::create(
            account_id: $this->account1->getId(),
            from: 'foo@testclient.com',
            to: ['ti.superest@gmail.com'],
            cc: ['testing@testclient.com'],
            bcc: ['gnitset@testclient.com'],
            subject: 'Test Email 2',
            body: 'This is the 2nd test email for filtering by client.',
            read: true,
            read_at: new \DateTime(),
            direction: Direction::INCOMING,
            folder_id: $this->folder2->getId(),
            attachments: false,
            processed_at: new \DateTime(),
        );

        $email3 = Email::create(
            account_id: $this->account2->getId(),
            from: 'bar@testclient.com',
            to: ['ti@testclient.com'],
            cc: ['testing@testclient.com'],
            bcc: ['gnitset@testclient.com'],
            subject: 'Email 3',
            body: 'This is the 3rd email for filtering by client.',
            read: true,
            read_at: new \DateTime('2023-10-01 12:00:00'),
            direction: Direction::INCOMING,
            folder_id: $this->folder3->getId(),
            attachments: false,
            processed_at: new \DateTime('2023-10-01 09:00:00'),
        );

        // Create email from different client (should not appear in results)
        $email4 = Email::create(
            account_id: $this->account3->getId(),
            from: 'test@differentclient.com',
            to: ['someone@external.com'],
            cc: [],
            bcc: [],
            subject: 'Different Client Email',
            body: 'This email should not appear in test client results.',
            direction: Direction::OUTGOING,
            origin: Origin::MANUAL,
            folder_id: $folder1->getId(),
            attachments: false,
            processed_at: new \DateTime(),
        );

        $this->emailRepository->save($email1);
        $this->emailRepository->save($email2);
        $this->emailRepository->save($email3);
        $this->emailRepository->save($email4);

        $this->email1 = $email1;
        $this->email2 = $email2;
        $this->email3 = $email3;
        $this->email4 = $email4;

        $filters = app()->tagged('email.filters');
        $this->emailFiltersService = new EmailFiltersService($filters);
        
        $this->useCase = new FilterEmailsByClient(
            $this->emailRepository,
            $this->emailFiltersService
        );
    });

    it('should filter emails by email address', function () {
        $input = FilterEmailsByClientInputData::validateAndCreate([
            'client' => $this->client,
            'filter' => EmailFilterData::validateAndCreate([
                'email_address' => 'ti.superest'
            ])->toArray()
        ]);

        $emails = $this->useCase->execute($input)->emails;

        expect($emails)->toHaveCount(2);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email 1');
        expect($emails[1]->getData()->getSubject())->toBe('Test Email 2');
    });

    it('should filter emails by folder', function () {
        $input = FilterEmailsByClientInputData::validateAndCreate([
            'client' => $this->client,
            'filter' => EmailFilterData::validateAndCreate([
                'folder_slug' => $this->folder1->getSlug()
            ])->toArray()
        ]);
        
        $emails = $this->useCase->execute($input)->emails;
        
        expect($emails)->toHaveCount(1);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email 1');
    });

    it('should filter emails by read status', function () {
        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_start_date' => '2023-10-01',
                    'read_end_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_start_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => false,
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_end_date' => '2023-10-01'
                ])->toArray()
            ]),
        ];

        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if($input->filter->read_start_date && $input->filter->read_end_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            } else if($input->filter->read_start_date && !$input->filter->read_end_date) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Email 3', 'Test Email 2']);
                }
            } else if ($input->filter->read_end_date && !$input->filter->read_start_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            } else {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email 1');
            }
        }
    });

    it('should filter emails by process date', function () {
        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_start_date' => '2023-10-01',
                    'process_end_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_start_date' => '2023-10-02',
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_end_date' => '2023-10-02',
                ])->toArray()
            ]),
        ];

        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if($input->filter->process_start_date && $input->filter->process_end_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            } else if($input->filter->process_start_date && !$input->filter->process_end_date) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email 1', 'Test Email 2']);
                }
            } else if ($input->filter->process_end_date && !$input->filter->process_start_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            }
        }
    });

    it('should filter emails by body', function () {
        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'body_contains' => 'test'
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'body_contains' => 'Nonexistent Body'
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email 1', 'Test Email 2']);
                }
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by subject', function () {
        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'subject_contains' => 'Test Email'
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'subject_contains' => 'Nonexistent Subject'
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email 1', 'Test Email 2']);
                }
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by direction', function () {
        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'direction' => Direction::OUTGOING
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'direction' => Direction::INCOMING
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email 1');
            } else {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email 2', 'Email 3']);
                }
            }
            $i++;
        }
    });

    it('should filter emails by complements', function () {
        DB::table('email_complements')->insert([
            'email_id' => $this->email1->getId(),
            'complement_data' => json_encode([
                'key1' => 'foo',
                'key2' => 'bar'
            ]),
            'created_at' => now(),
        ]);

        $inputs = [
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'complements' => [json_decode(json_encode([
                        'key1' => 'foo'
                    ]))]
                ])->toArray()
            ]),
            FilterEmailsByClientInputData::validateAndCreate([
                'client' => $this->client,
                'filter' => EmailFilterData::validateAndCreate([
                    'complements' => [json_decode(json_encode([
                        'nonexistent_key' => 'value'
                    ]))]
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->useCase->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email 1');
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by multiple criteria', function () {
        $input = FilterEmailsByClientInputData::validateAndCreate([
            'client' => $this->client,
            'filter' => EmailFilterData::validateAndCreate([
                'email_address' => 'ti.superest',
                'subject_contains' => '2',
            ])->toArray()
        ]);

        $emails = $this->useCase->execute($input)->emails;

        expect($emails)->toHaveCount(1);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email 2');
    });

    it('should only return emails from the specified client', function () {
        // Test with no filters to get all emails from the client
        $input = FilterEmailsByClientInputData::validateAndCreate([
            'client' => $this->client,
            'filter' => EmailFilterData::validateAndCreate([])->toArray()
        ]);

        $emails = $this->useCase->execute($input)->emails;

        // Should return 3 emails from testclient.com accounts, not the differentclient.com email
        expect($emails)->toHaveCount(3);
        
        $subjects = array_map(fn($email) => $email->getData()->getSubject(), $emails);
        expect($subjects)->toContain('Test Email 1');
        expect($subjects)->toContain('Test Email 2');
        expect($subjects)->toContain('Email 3');
        expect($subjects)->not->toContain('Different Client Email');
    });

    it('should return empty result for different client', function () {
        $input = FilterEmailsByClientInputData::validateAndCreate([
            'client' => $this->otherClient,
            'filter' => EmailFilterData::validateAndCreate([])->toArray()
        ]);

        $emails = $this->useCase->execute($input)->emails;

        // The differentclient.com email should be returned for the other client
        expect($emails)->toHaveCount(1);
        expect($emails[0]->getData()->getSubject())->toBe('Different Client Email');
    });
});