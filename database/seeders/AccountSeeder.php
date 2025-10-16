<?php

namespace Database\Seeders;

use App\Domain\Entities\Account;
use App\Domain\Enums\AccountType;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\AccountRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function __construct(
        private AccountRepository $accountRepository
    ) {}
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->delete();

        $account_list = [];

        $account_list[] = Account::create(
            id: '4e600fae-8e69-4a42-80ef-5784d3c1ec36',
            email_address: 'root@gruposuper.com.br',
            password: 'eyJpdiI6IiswdjQvejh3TytSSWdoTmcwcUtTOUE9PSIsInZhbHVlIjoiajJ2ako3ZDhSUzNXRGZrdHpXYkJBQT09IiwibWFjIjoiMTRkMmYyYTg2NjhhNzg2OTQyNjQwZTUxNTE1MTc5Y2I5MjMyMTczMjZjODYxODQzOWU0MDgwZWMxYzI4ODM0NiIsInRhZyI6IiJ9',
            host: 'mail.gruposuper.com.br',
            port: 587,
            token: 'e72f66b2-b5b0-4e05-9a84-ca425e866f65',
            username: 'root',
            type: AccountType::SENDER
        );

        $account_list[] = Account::create(
            id: 'd7b032b8-1f7a-4e67-8c28-f7848dcc94f3',
            email_address: 'root@superestagios.com.br',
            password: '',
            host: 'mail.gruposuper.com.br',
            port: 587,
            token: 'c1072a02-c8bb-439d-8a54-59cbab887610',
            username: 'root',
            type: AccountType::SENDER
        );

        $account_list[] = Account::create(
            id: '0bd263af-6006-4973-8186-df82772a4f9b',
            email_address: 'ti@superestagios.com.br',
            password: Crypto::encrypt('BCNsddU3IeXeOiXGcUk3ie0d7YYabzi9+gzgJ2Vdix6T'),
            host: 'email-smtp.us-east-1.amazonaws.com',
            port: 587,
            token: '7463191b-d354-445e-bea4-356812197491',
            username: 'AKIAVAVZ53YBCDCS4VVV',
            type: AccountType::SENDER
        );

        $account_list[] = Account::create(
            id: '89622bd4-2dc5-49f8-8b15-755825fa73e5',
            email_address: 'estagios@superestagios.com.br',
            password: Crypto::encrypt('BCNsddU3IeXeOiXGcUk3ie0d7YYabzi9+gzgJ2Vdix6T'),
            host: 'email-smtp.us-east-1.amazonaws.com',
            port: 587,
            token: 'dda281ad-48d1-4316-908c-3d590caf583c',
            username: 'AKIAVAVZ53YBCDCS4VVV',
            type: AccountType::SENDER
        );

        $account_list[] = Account::create(
            id: 'b485124b-87be-45a7-b65a-491df797da98',
            email_address: 'ti@gruposuper.com.br',
            password: '',
            host: 'mail.gruposuper.com.br',
            port: 587,
            token: '9939a69b-5631-4890-aa5e-be21a3f74e52',
            username: 'ti',
            type: AccountType::SENDER
        );

        foreach ($account_list as $account) {
            $this->accountRepository->save($account);
        }
    }
}
