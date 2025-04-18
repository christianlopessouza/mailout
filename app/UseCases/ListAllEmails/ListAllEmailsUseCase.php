<?php

namespace App\UseCases\ListAllEmails;

use App\Domain\Repositories\EmailRepositoryInterface;
use App\UseCases\ListAllEmails\ListAllEmailsRequest;
use App\UseCases\ListAllEmails\ListAllEmailsResponse;

class ListAllEmailsUseCase implements ListAllEmailsUseCaseInterface
{
    private EmailRepositoryInterface $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function execute(ListAllEmailsRequest $listAllEmailsRequest): ListAllEmailsResponse
    {
        $emails = $this->emailRepository->listAllEmails($listAllEmailsRequest->getEmailId());
        return new ListAllEmailsResponse($emails);
    }
}