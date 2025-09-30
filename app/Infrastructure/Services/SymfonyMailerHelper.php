<?php

namespace App\Infrastructure\Services;

use App\Helper\Crypto;

class SymfonyMailerHelper
{
    public static function dsn($credentials): string
    {
        return strtr('smtp://{address}:{password}@{host}:{port}', [
            '{address}' => urlencode($credentials->username),
            '{password}' => urlencode(Crypto::decrypt($credentials->password)),
            '{host}' => $credentials->host,
            '{port}' => $credentials->port
        ]);
    }
}