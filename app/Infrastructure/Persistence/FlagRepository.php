<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Flag;

interface FlagRepository
{
    public function save(Flag $flag);
    public function findByName($flag, $client_id);
}
