<?php

namespace App\Infrastructure\Services;

use App\Data\Input\EmailAuthenticationInputData;

interface EmailAuthenticationService
{
    /**
     * Authenticate the email using the provided credentials.
     *
     * @param \App\Data\Input\EmailAuthenticationInputData $params
     * @return bool  Returns true if authentication is successful, false otherwise.
     */
    public function authenticate(EmailAuthenticationInputData $params): bool;
}
