<?php

namespace App\UseCases\ListEmails;

interface ListEmailsUseCaseInterface
{
    public function execute(ListEmailsRequest $request): ListEmailsResponse;
}
