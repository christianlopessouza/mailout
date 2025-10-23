<?php

namespace App\UseCases;

use App\Infrastructure\Persistence\EmailRepository;
use App\Data\Input\ListEmailsByThreadInputData;
use App\Domain\Entities\Email;

class ListEmailsByThread
{
    public function __construct(
        private readonly EmailRepository $emailRepository
    ) {}

    /**
     * @return Email[]
     */
    public function execute(ListEmailsByThreadInputData $input): array
    {
        return $this->emailRepository->findByThreadId($input->thread_id);
    }
}
