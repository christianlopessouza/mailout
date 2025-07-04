<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Domain\Entities\Flag;
use App\Infrastructure\Persistence\FlagRepository;
use Illuminate\Support\Facades\DB;

class FacadesFlagRepository implements FlagRepository
{
    private function map(object $data): Flag
    {
        return Flag::create(
            id: $data->id,
            name: $data->name,
            account_id: $data->account_id ?? null,
            client_id: $data->client_id ?? null,
        );
    }
    public function save(Flag $flag): void
    {
        $now = now();
        DB::table('flags')->updateOrInsert(
            ['id' => $flag->getId()],
            [
                'name' => $flag->getName(),
                'account_id' => $flag->getAccountId(),
                'client_id' => $flag->getClientId(),
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }

    public function findByName($flag, $client_id): ?Flag
    {
        $data = DB::table('flags')->where('name', $flag)->where('client_id', $client_id)->first();
        if (!$data)
            return null;
        return $this->map($data);
    }
}
