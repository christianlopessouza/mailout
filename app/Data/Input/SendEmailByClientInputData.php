<?php

namespace App\Data\Input;

use App\Data\EmailData;
use App\Domain\Entities\Client;
use Spatie\LaravelData\Data;

class EmailDataClient extends EmailData
{
    public string $from;

    public static function rules(): array
    {
        return [
            'from' => ['email', 'required'],
        ];
    }
}

class SendEmailByClientInputData extends Data
{
    public function __construct(
        public readonly Client $client,
        public readonly EmailDataClient $email
    ) {}
}
