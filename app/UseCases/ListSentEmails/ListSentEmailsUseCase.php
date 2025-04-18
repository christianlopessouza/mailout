<?php

namespace App\UseCases\ListSentEmails;

use App\Domain\Repositories\EmailRepositoryInterface;

class ListSentEmailsUseCase implements ListSentEmailsUseCaseInterface
{
    private EmailRepositoryInterface $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function execute(ListSentEmailsRequest $request): ListSentEmailsResponse
    {
        $email = $request->getEmail();
        $sentEmails = $this->emailRepository->listSentEmails($email);
        return new ListSentEmailsResponse($sentEmails);
    }
}