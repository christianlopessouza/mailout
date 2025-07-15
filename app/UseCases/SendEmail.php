<?php

namespace App\UseCases;

use App\Data\EmailSenderSend;
use App\Data\Output\SendEmailOutputData;
use App\Data\Input\SendEmailInputData;
use App\Data\SendEmailServiceData;
use App\Domain\Entities\Attachment;
use App\Domain\Entities\Email;
use App\Domain\Enums\AttachmentStatus;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Domain\Enums\Origin;
use App\Errors\EmailSendFailureError;
use App\Errors\FolderNotFoundError;
use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Services\EmailSenderService;
use App\UseCases\Services\SendEmailService;

class SendEmail
{
    public function __construct(
        public readonly EmailSenderService $emailSenderService,
        public readonly EmailRepository $emailRepository,
        public readonly FolderRepository $folderRepository,
        public readonly EmailComplementService $emailComplementService,
        private readonly EmailComplementRepository $emailComplementRepository,
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
