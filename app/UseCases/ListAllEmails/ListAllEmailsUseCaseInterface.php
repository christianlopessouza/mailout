<?php

namespace App\UseCases\ListAllEmails;

use App\UseCases\ListAllEmails\ListAllEmailsRequest;
use App\UseCases\ListAllEmails\ListAllEmailsResponse;
interface ListAllEmailsUseCaseInterface
{
    public function execute(ListAllEmailsRequest $listAllEmailsRequest): ListAllEmailsResponse;
}