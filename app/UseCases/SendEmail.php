<?php

namespace App\UseCases;

use App\Data\Output\SendEmailOutputData;
use App\Data\Input\SendEmailInputData;
use App\Data\SendEmailServiceData;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Domain\Services\EmailComplementService;
use App\Domain\Contracts\IEmailSenderService;
use App\UseCases\Services\SendEmailService;

class SendEmail
{
    public function __construct(
        private readonly SendEmailService $sendEmailService,
    ) {}

    public function execute(SendEmailInputData $input): SendEmailOutputData
    {
        $send_email = $this->sendEmailService->execute(
            SendEmailServiceData::validateAndCreate([
                'account' => $input->account,
                'email' => $input->email->toArray()
            ])
        );

        $output = new SendEmailOutputData(
            email: $send_email->email
        );
        return $output;
    }
}
