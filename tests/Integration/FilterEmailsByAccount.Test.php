<?php

use App\Data\EmailFilterData;
use App\Data\Input\FilterEmailsByAccountInputData;
use App\Domain\Entities\Account;
use App\Domain\Entities\Email;
use App\Domain\Entities\Flag;
use App\Domain\Entities\Folder;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesemailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFlagRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\UseCases\FilterEmailsByAccount;
use App\Infrastructure\Support\EmailFiltersMapper;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

describe('Filter Emails By Account', function () {

    beforeEach(function () {
        DB::table('emails')->delete();
        DB::table('email_search_tokens')->delete();
        DB::table('accounts')->delete();
        DB::table('folders')->delete();
        DB::table('flags')->delete();
        DB::table('email_flags')->delete();
        DB::table('email_complements')->delete();

        $this->emailRepository = new FacadesemailRepository();
        $this->accountRepository = new FacadesAccountRepository();
        $this->folderRepository = new FacadesFolderRepository();

        $this->accountRepository->save(
            Account::create(
                email_address: 'filterTesting@superestagios.com.br',
                password: Crypto::encrypt('password123'),
                host: 'email-smtp.us-east-1.amazonaws.com',
                port: 587,
                token: null
            )
        );

        $this->account = $this->accountRepository->findByEmail('filterTesting@superestagios.com.br');

        $folder1 = Folder::create(
                slug: 'FilterTesting',
                name: 'Filter Testing',
                account_id: $this->account->getId(),
        );

        $this->folderRepository->save(
            $folder1
        );

        $folder2 = Folder::create(
            slug: 'none',
            name: 'None',
            account_id: $this->account->getId(),
        );

        $this->folderRepository->save(
            $folder2
        );

        $this->folder = $folder1;

        $email = Email::create(
                account_id: $this->account->getId(),
                from: 'filterTesting@superestagios.com.br',
                to: ['ti.superest@gmail.com'],
                cc: ['testing@superestagios.com.br'],
                bcc: ['gnitset@superestagios.com.br'],
                subject: 'Test Email',
                body: 'This is a test email for filtering by account.',
                direction: Direction::OUTGOING,
                folder_id: $this->folder->getId(),
                attachments: false,
                origin: Origin::MANUAL,
                processed_at: new \DateTime()
        );

        $email2 = Email::create(
                account_id: $this->account->getId(),
                from: 'filterTesting@superestagios.com.br',
                to: ['ti.superest@gmail.com'],
                cc: ['testing@superestagios.com.br'],
                bcc: ['gnitset@superestagios.com.br'],
                subject: 'Test Email 2',
                body: 'This is the 2 test email for filtering by account.',
                read: true,
                read_at: new \DateTime(),
                direction: Direction::INCOMING,
                folder_id: $folder2->getId(),
                attachments: false,
                processed_at: new \DateTime(),
        );

        $email3 = Email::create(
                account_id: $this->account->getId(),
                from: 'filterTesting@superestagios.com.br',
                to: ['ti@superestagios.com'],
                cc: ['testing@superestagios.com.br'],
                bcc: ['gnitset@superestagios.com.br'],
                subject: 'Email 3',
                body: 'This is the 3 email for filtering by account.',
                read: true,
                read_at: new \DateTime('2023-10-01 12:00:00'),
                direction: Direction::INCOMING,
                folder_id: $folder2->getId(),
                attachments: false,
                processed_at: new \DateTime('2023-10-01 09:00:00'),
        );

        $this->emailRepository->save(
            $email
        );

        $this->emailRepository->save(
            $email2
        );

        $this->emailRepository->save(
            $email3
        );

        $this->email = $email;

        $filters = $filters = app()->tagged('email.filters');

        $this->filterEmailsByAccount = new FilterEmailsByAccount(
            emailRepository: $this->emailRepository,
            emailFiltersService: new EmailFiltersMapper($filters)
        );
    });

    it('should filter emails by email address', function () {
        $input = FilterEmailsByAccountInputData::validateAndCreate([
            'account' => $this->account,
            'filter' => EmailFilterData::validateAndCreate([
                'email_address' => 'ti.superest'
            ])->toArray()
        ]);


        $emails = $this->filterEmailsByAccount->execute($input)->emails;

        expect($emails)->toHaveCount(2);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email');
        expect($emails[1]->getData()->getSubject())->toBe('Test Email 2');
    });

    it('should filter emails by folder', function () {
        $input = FilterEmailsByAccountInputData::validateAndCreate([
            'account' => $this->account,
            'filter' => EmailFilterData::validateAndCreate([
                'folder_slug' => $this->folder->getSlug()
            ])->toArray()
        ]);
        
        $emails = $this->filterEmailsByAccount->execute($input)->emails;
        
        expect($emails)->toHaveCount(1);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email');
    });

    it('should filter emails by read status', function () {
        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_start_date' => '2023-10-01',
                    'read_end_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_start_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => false,
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'read' => true,
                    'read_end_date' => '2023-10-01'
                ])->toArray()
            ]),
        ];

        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

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
                expect($emails[0]->getData()->getSubject())->toBe('Test Email');
            }
        }
    });

    it('should filter emails by process date', function () {
        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_start_date' => '2023-10-01',
                    'process_end_date' => '2023-10-01'
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_start_date' => '2023-10-02',
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'process_end_date' => '2023-10-02',
                ])->toArray()
            ]),
        ];

        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if($input->filter->process_start_date && $input->filter->process_end_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            } else if($input->filter->process_start_date && !$input->filter->process_end_date) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email', 'Test Email 2']);
                }
            } else if ($input->filter->process_end_date && !$input->filter->process_start_date) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Email 3');
            }
        }
    });

    it('should filter emails by body', function () {
        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'body_contains' => 'test'
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'body_contains' => 'Nonexistent Body'
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email', 'Test Email 2']);
                }
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by subject', function () {
        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'subject_contains' => 'Test Email'
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'subject_contains' => 'Nonexistent Subject'
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email', 'Test Email 2']);
                }
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by direction', function () {
        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'direction' => Direction::OUTGOING
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'direction' => Direction::INCOMING
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email');
            } else {
                expect($emails)->toHaveCount(2);
                foreach ($emails as $email) {
                    expect($email->getData()->getSubject())->toBeIn(['Test Email 2', 'Email 3']);
                }
            }
            $i++;
        }
    });

    it('should filter emails by flags', function () {
        $flag_rep = new FacadesFlagRepository();

        $importantFlag = Flag::create(
            name: 'important',
            account_id: $this->account->getId()
        );
        
        $flag_rep->save(
            $importantFlag
        );

        $urgentFlag = Flag::create(
            name: 'urgent',
            account_id: $this->account->getId()
        );

        $flag_rep->save(
            $urgentFlag
        );

        DB::table('email_flags')->insert([
            'id' => UUID::v7(),
            'email_id' => $this->email->getId(),
            'flag_id' => $importantFlag->getId(),
            'created_at' => now(),
        ]);
        DB::table('email_flags')->insert([
            'id' => UUID::v7(),
            'email_id' => $this->email->getId(),
            'flag_id' => $urgentFlag->getId(),
            'created_at' => now(),
        ]);

        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'flag_names' => ['important', 'urgent']
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'flag_names' => ['Nonexistent Flag']
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if($i === 0) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email');
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by complements', function () {
        DB::table('email_complements')->insert([
            'email_id' => $this->email->getId(),
            'complement_data' => json_encode([
                'key1' => 'foo',
                'key2' => 'bar'
            ]),
            'created_at' => now(),
        ]);

        $inputs = [
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'complements' => [json_decode(json_encode([
                        'key1' => 'foo'
                    ]))]
                ])->toArray()
            ]),
            FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $this->account,
                'filter' => EmailFilterData::validateAndCreate([
                    'complements' => [json_decode(json_encode([
                        'nonexistent_key' => 'value'
                    ]))]
                ])->toArray()
            ]),
        ];
        $i = 0;
        foreach ($inputs as $input) {
            $emails = $this->filterEmailsByAccount->execute($input)->emails;

            if ($i === 0) {
                expect($emails)->toHaveCount(1);
                expect($emails[0]->getData()->getSubject())->toBe('Test Email');
            } else {
                expect($emails)->toHaveCount(0);
            }
            $i++;
        }
    });

    it('should filter emails by complements with OR conditions', function () {
        // Cria dois emails com complementos diferentes
        $email1 = Email::create(
            account_id: $this->account->getId(),
            from: 'test@example.com',
            to: ['recipient1@example.com'],
            cc: [],
            bcc: [],
            subject: 'Test Email OR 1',
            body: 'Body 1',
            direction: Direction::INCOMING,
            folder_id: $this->folder->getId(),
            attachments: false,
            origin: Origin::MANUAL,
            processed_at: new \DateTime()
        );

        $email2 = Email::create(
            account_id: $this->account->getId(),
            from: 'test@example.com',
            to: ['recipient2@example.com'],
            cc: [],
            bcc: [],
            subject: 'Test Email OR 2',
            body: 'Body 2',
            direction: Direction::INCOMING,
            folder_id: $this->folder->getId(),
            attachments: false,
            origin: Origin::MANUAL,
            processed_at: new \DateTime()
        );

        $this->emailRepository->save($email1);
        $this->emailRepository->save($email2);

        DB::table('email_complements')->insert([
            [
                'email_id' => $email1->getId(),
                'complement_data' => json_encode([
                    'status' => 1,
                    'copia' => 1
                ]),
                'created_at' => now(),
            ],
            [
                'email_id' => $email2->getId(),
                'complement_data' => json_encode([
                    'status' => 0,
                    'copia' => 2
                ]),
                'created_at' => now(),
            ]
        ]);

        // Testa com condições OR: (status=1 AND copia=1) OR (status=0 AND copia=2)
        $input = FilterEmailsByAccountInputData::validateAndCreate([
            'account' => $this->account,
            'filter' => EmailFilterData::validateAndCreate([
                'complements' => [
                    json_decode(json_encode(['status' => 1, 'copia' => 1])),
                    json_decode(json_encode(['status' => 0, 'copia' => 2]))
                ]
            ])->toArray()
        ]);

        $emails = $this->filterEmailsByAccount->execute($input)->emails;

        // Deve retornar ambos os emails
        expect($emails)->toHaveCount(2);
        
        // Ordena por subject para ter ordem previsível
        usort($emails, fn($a, $b) => strcmp($a->getData()->getSubject(), $b->getData()->getSubject()));
        
        expect($emails[0]->getData()->getSubject())->toBe('Test Email OR 1');
        expect($emails[1]->getData()->getSubject())->toBe('Test Email OR 2');
    });

    it('should filter emails by multiple criteria', function () {
        $input = FilterEmailsByAccountInputData::validateAndCreate([
            'account' => $this->account,
            'filter' => EmailFilterData::validateAndCreate([
                'email_address' => 'ti.superest',
                'subject_contains' => '2',
            ])->toArray()
        ]);

        $emails = $this->filterEmailsByAccount->execute($input)->emails;

        expect($emails)->toHaveCount(1);
        expect($emails[0]->getData()->getSubject())->toBe('Test Email 2');
    });
});