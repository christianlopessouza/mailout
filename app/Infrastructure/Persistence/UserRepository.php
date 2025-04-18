<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\EmailAccount;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $userData = DB::table('users')->where('id', $id)->first();

        if (!$userData) {
            return null;
        }

        $id = $userData->id;
        $emailAccounts = DB::table('user_accounts')->where('user_id', '=', $id)->get();
        $emailList = [];

        foreach ($emailAccounts as $email) {
            $emailList[] = new EmailAccount(
                $email->id,
                $email->email,
                $email->password,
                $email->user_id
            );
        }

        return new User(
            $userData->id,
            $userData->user,
            $userData->password,
            $emailList
        );
    }

    public function findByUsername(string $username): ?User
    {
        $userData = DB::table('users')->where('user', $username)->first();

        if (!$userData) {
            return null;
        }

        return $this->findById($userData->id);
    }

    public function exists(string $email): bool
    {
        return DB::table('user_accounts')->where('email', $email)->exists();
    }

    public function getEmailAccount(string $email): ?EmailAccount
    {
        $emailAccount = DB::table('user_accounts')->where('email', $email)->first();

        if (!$emailAccount) {
            return null;
        }

        return new EmailAccount(
            $emailAccount->id,
            $emailAccount->email,
            $emailAccount->password,
            $emailAccount->user_id
        );
    }

}