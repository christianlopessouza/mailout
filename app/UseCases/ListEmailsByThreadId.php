<?php

namespace App\UseCases;

use App\Infrastructure\Persistence\EmailRepository;
use App\Data\Input\ListEmailsByThreadIdInputData;
use App\Domain\Entities\Email;

class ListEmailsByThreadId
{
    public function __construct(
        private readonly EmailRepository $emailRepository
    ) {}

    /**
     * @return Email[]
     */
    public function execute(ListEmailsByThreadIdInputData $input): array
    {
        return $this->emailRepository->findByThreadId($input->thread_id);
    }
}
