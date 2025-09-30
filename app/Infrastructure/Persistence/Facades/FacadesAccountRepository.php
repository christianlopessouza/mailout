<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\Account;
use App\Domain\Enums\AccountType;
use App\Infrastructure\Persistence\AccountRepository;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\Regex;

class FacadesAccountRepository implements AccountRepository
{
    private function map(object $data): Account
    {
        return Account::create(
            email_address: $data->email_address,
            password: $data->password,
            host: $data->host,
            port: $data->port,
            type: AccountType::from($data->type),
            id: $data->id,
            token: $data->token,
            username: $data->username
        );
    }

    public function save(Account $account): void
    {

        $now = now();

        DB::table('accounts')->updateOrInsert(
            ['id' => $account->getId()],
            [
                'email_address' => $account->getEmailAddress(),
                'password' => $account->getPassword(),
                'host' => $account->getHost(),
                'port' => $account->getPort(),
                'type' => $account->getType(),
                'token' => $account->getToken(),
                'created_at' => $now,
                'updated_at' => $now,
                'username' => $account->getUsername()
            ]
        );
    }

    public function findById(string $id): ?Account
    {
        $data = DB::table('accounts')->where('id', $id)->first();
        return $data ? $this->map($data) : null;
    }

    public function fetchByClient(string $client_id): ?array
    {
        $client = DB::table('clients')->where('id', $client_id)->first();
        if (!$client || !isset($client->domain)) {
            return null;
        }

        $email_domain = '%' . $client->domain;
        $data = DB::table('accounts')
            ->where('email_address', 'ilike', $email_domain)
            ->get();

        $accounts = [];
        foreach ($data as $item) {
            $accounts[] = $this->map($item);
        }

        return $accounts;
    }

    public function validateClientAuthorization(string $client_id, array $email_list): bool
    {
        $client_accounts = $this->fetchByClient($client_id);
        if (!$client_accounts)
            return false;

        $client_account_ids = array_map(
            fn(Account $account) => $account->getId(),
            $client_accounts
        );

        return count(array_intersect($client_account_ids, $email_list)) === count($email_list);
    }

    public function findByEmail(string $email): ?Account
    {
        $data = DB::table('accounts')->where('email_address', $email)->first();
        return $data ? $this->map($data) : null;
    }

    public function findByUsername(?string $username): ?Account
    {
        $data = DB::table('accounts')->where('usename', $username)->first();
        return $data ? $this->map($data) : null;
    }
    public function findByToken(string $token): ?Account
    {
        $data = DB::table('accounts')->where('token', $token)->first();
        return $data ? $this->map($data) : null;
    }
}
