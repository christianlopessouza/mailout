<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\Account;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;

class InMemoryAccountRepository implements AccountRepository
{
    /** @var Account[] */
    private array $data = [];
    public function __construct(private ?ClientRepository $clientRepository = null)
    {
    }
    public function save(Account $account): void
    {
        $found = array_filter($this->data, function ($item) use ($account) {
            return $item->getId() === $account->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $account;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $account;
        }
    }

    public function fetchByClient(string $client_id): ?array
    {
        $client = $this->clientRepository->findById($client_id);
        if (!$client)
            null;

        $domain = $client->getDomain();

        return array_filter($this->data, function ($account) use ($domain) {
            return strpos($account->getEmailAddress(), $domain) > 0;
        });
    }
    public function validateClientAuthorization(string $client_id, array $email_list): bool
    {
        $client_accounts = $this->fetchByClient($client_id);
        if (!$client_accounts)
            return false;

        $client_accounts_emails = array_map(function (Account $account) {
            return $account->getId();
        }, $client_accounts);

        return count(array_intersect($client_accounts_emails, $email_list)) === count($email_list);
    }
    public function findById(string $id): ?Account
    {
        $finder = array_filter($this->data, function (Account $account) use ($id) {
            return $account->getId() === $id;
        });

        $data = count($finder) ? reset($finder) : null;
        return $data;
    }
    public function findByEmail(string $email): ?Account
    {
        $finder = array_filter($this->data, function (Account $account) use ($email) {
            return $account->getEmailAddress() === $email;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }

    public function findByToken(string $token): ?Account
    {
        $finder = array_filter($this->data, function (Account $account) use ($token) {
            return $account->getToken() === $token;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }

    public function findByUsername(?string $username): ?Account
    {
        $finder = array_filter($this->data, function (Account $account) use ($username) {
            return $account->getUsername() === $username;
        });
        $data = count($finder) ? reset($finder) : null;
        return $data;
    }
}
