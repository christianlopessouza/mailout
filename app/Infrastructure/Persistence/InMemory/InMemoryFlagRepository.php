<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Entities\Flag;
use App\Infrastructure\Persistence\FlagRepository;

class InMemoryFlagRepository implements FlagRepository
{
    /** @var Flag[] */
    private array $data = [];
    public function save(Flag $flag)
    {
        $found = array_filter($this->data, function ($item) use ($flag) {
            return $item->getId() === $flag->getId();
        });
        if (count($found) === 0) {
            $this->data[] = $flag;
        } else {
            $key = array_search($found, array_column($this->data, 'id'));
            $this->data[$key] = $flag;
        }
        return true;
    }
    public function findByName($name, $client_id)
    {
        $finder = array_filter($this->data, function (Flag $flag) use ($name, $client_id) {
            return $flag->getName() === $name &&
                $flag->getClientId() === $client_id;
        });

        $data = $finder[0] ?? null;

        return $data;
    }
}
