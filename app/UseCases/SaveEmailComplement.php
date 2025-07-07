<?php

namespace App\UseCases;

use App\Infrastructure\Persistence\EmailComplementRepository;

class SaveEmailComplement
{
    private $emailComplementRepository;

    public function __construct(EmailComplementRepository $emailComplementRepository)
    {
        $this->emailComplementRepository = $emailComplementRepository;
    }

    public function executeEmailComplement(array $emailData, string $emailId): void
    {
        $this->emailComplementRepository->saveEmailComplement($emailData, $emailId);
    }

    public function executeEmailTemplate(array $templateData, string $clientId): void
    {
        $this->emailComplementRepository->saveEmailComplementTemplate($templateData, $clientId);
    }
}
