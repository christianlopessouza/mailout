<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\EmailQueue;
use App\Domain\Enums\EmailStatus;
use App\Infrastructure\Persistence\EmailQueueRepository;

class InMemoryEmailQueueRepository implements EmailQueueRepository
{
    /** @var EmailQueue[] */
    private array $data = [];
    public function save(EmailQueue $email): bool
    {
        $found = array_filter($this->data, function ($item) use ($email) {
            return $item->getId() === $email->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $email;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $email;
        }
        return true;
    }

    public function saveAll(array $emails): bool
    {
        foreach ($emails as $email) {
            $this->save($email);
        }
        return true;
    }

    public function fetchPending(int $amount): array
    {
        $emails = array_slice(
            array_filter($this->data, function ($email) {
                return $email->getStatus() === EmailStatus::PENDING;
            }),
            0,
            $amount
        );

        return $emails;
    }
}
