<?php

namespace App\Infrastructure\Services;

use App\Data\EmailAuthentication;

interface EmailAuthenticationService
{
    /**
     * Authenticate the email using the provided credentials.
     *
     * @param \App\Data\EmailAuthentication $params
     * @return bool  Returns true if authentication is successful, false otherwise.
    */
    public function authenticate(EmailAuthentication $params): bool;
}
