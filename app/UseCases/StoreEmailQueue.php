<?php

namespace App\UseCases;

use App\Data\Input\StoreEmailQueueInputData;
use App\Data\Output\StoreEmailQueueOutputData;

use App\Domain\Entities\EmailQueue;
use App\Domain\Entities\Flag;
use App\Errors\ClientNotFoundError;
use App\Errors\EmailQueueEmptyError;
use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailQueueRepository;
use App\Infrastructure\Persistence\FlagRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Util\UUID;

class StoreEmailQueue
{
    /** @var Flag[] */
    public array $flag_cache = [];
    public function __construct(
        public readonly FolderRepository $folderRepository,
        public readonly ClientRepository $clientRepository,
        public readonly EmailQueueRepository $emailQueueRepository,
        public readonly FlagRepository $flagRepository,
    ) {
    }

    public function execute(StoreEmailQueueInputData $input): StoreEmailQueueOutputData
    {
        $emails = $input->emails;
        $client_id = $input->client_id;

        $client = $this->clientRepository->findById($client_id);
        if (!$client)
            throw new ClientNotFoundError();

        if (empty($emails))
            throw new EmailQueueEmptyError();

        $client_domain = $client->getDomain();
        $batch_id = UUID::v4();

        $email_data = array_map(function ($email) use ($client_domain, $batch_id, $client_id) {
            $email = (object) $email;
            if (!str_contains($email->from, '@' . $client_domain)) {
                throw new UnauthorizedDomainError();
            }

            if (isset($email->flag) && $email->flag) {
                $flag = $this->findFlag($email->flag, $client_id);
                $flag_id = $flag->getId();
            }

            return EmailQueue::create(
                from: $email->from,
                to: $email->to,
                cc: $email->cc,
                bcc: $email->bcc,
                subject: $email->subject,
                body: $email->body,
                attachments: $email->attachments,
                batch_id: $batch_id,
                external_id: $email->external_id,
                flag_id: $flag_id ?? null
            );
        }, $emails);

        print_r($email_data);

        $insertedSuccessfully = $this->emailQueueRepository->saveAll($email_data);
        if (!$insertedSuccessfully)
            throw new \Exception('Error creating emails');

        $email_external = array_map(function (EmailQueue $email) {
            return [
                'id' => $email->getId(),
                'external_id' => $email->getExternalId()
            ];
        }, $email_data);

        $output = new StoreEmailQueueOutputData(
            emails: $email_external
        );

        return $output;
    }

    private function findFlag($flag, $client_id)
    {
        if (isset($this->flag_cache[$flag])) {
            return $this->flag_cache[$flag];
        }

        $flag = $this->flagRepository->findByName($flag, $client_id);
        if (!$flag) {
            $flag = Flag::create(name: $flag, client_id: $client_id);
            $this->flagRepository->save($flag);
        }
        return $flag;
    }
}
