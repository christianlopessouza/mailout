<?php

namespace App\UseCases\ListSentEmails;

interface ListSentEmailsUseCaseInterface
{
    public function execute(ListSentEmailsRequest $request): ListSentEmailsResponse;
}