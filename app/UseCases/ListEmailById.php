<?php

namespace App\UseCases;

use App\Infrastructure\Persistence\EmailRepository;
use App\Data\Input\ListEmailByIdInputData;
use App\Domain\Entities\Email;

class ListEmailById
{
    public function __construct(
        private readonly EmailRepository $emailRepository
    ) {}

    public function execute(ListEmailByIdInputData $input): ?Email
    {
        return $this->emailRepository->findById($input->id);
    }
}
