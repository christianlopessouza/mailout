<?php

namespace App\Infrastructure\Services;

use App\Data\Credentials;
use App\Helper\Crypto;
use Illuminate\Support\Facades\Crypt;

class SymfonyMailerHelper
{
    public static function dsn(Credentials $credentials): string
    {
        $login = $credentials->username ?? $credentials->email_address;
        $password = Crypto::decrypt($credentials->password);
        return strtr('smtp://{address}:{password}@{host}:{port}', [
            '{address}' => urlencode($login),
            '{password}' => urlencode($password),
            '{host}' => $credentials->host,
            '{port}' => $credentials->port
        ]);
    }
}