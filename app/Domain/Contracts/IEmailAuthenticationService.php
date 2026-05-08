<?php

namespace App\Domain\Contracts;

use App\Data\EmailAuthentication;

interface IEmailAuthenticationService
{
    /**
     * Authenticate the email using the provided credentials.
     *
     * @param \App\Data\EmailAuthentication $params
     * @return bool  Returns true if authentication is successful, false otherwise.
    */
    public function authenticate(EmailAuthentication $params): bool;
}
