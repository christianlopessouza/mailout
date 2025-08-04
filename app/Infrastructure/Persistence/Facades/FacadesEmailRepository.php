<?php

namespace App\Infrastructure\Persistence\Facades;

use App\Data\EmailFilter;
use App\Data\EmailSearchTokens;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Infrastructure\Persistence\EmailRepository;
use Illuminate\Support\Facades\DB;
use App\Data\PaginatedEmailsData;
use App\Data\PaginationData;

class FacadesEmailRepository implements EmailRepository
{
    private function map(object $data): Email
    {
        return Email::create(
            id: $data->id,
            account_id: $data->account_id,
            from: $data->from,
            to: json_decode($data->to),
            cc: json_decode($data->cc),
            bcc: json_decode($data->bcc),
            subject: $data->subject,
            body: $data->body,
            direction: Direction::from($data->direction),
            origin: ($data->origin) ? Origin::from($data->origin) : null,
            folder_id: $data->folder_id,
            read: $data->read,
            read_at: $data->read_at ? new \DateTime($data->read_at) : null,
            attachments: $data->attachments,
            thread_id: $data->thread_id,
            processed_at: new \DateTime($data->processed_at),
            external_id: $data->external_id,
            deleted: $data->deleted,
            failed: $data->failed,
            reply_to: $data->reply_to
        );
    }

    public function save(Email $email): void
    {
        $now = now();
        DB::table('emails')->updateOrInsert(
            ['id' => $email->getId()],
            [
                'from' => $email->getData()->getFrom(),
                'to' => json_encode($email->getData()->getTo()),
                'cc' => json_encode($email->getData()->getCc()),
                'bcc' => json_encode($email->getData()->getBcc()),
                'subject' => $email->getData()->getSubject(),
                'body' => $email->getData()->getBody(),
                'direction' => $email->getDirection()->value,
                'read' => $email->getRead(),
                'folder_id' => $email->getFolderId(),
                'attachments' => json_encode($email->getData()->getAttachments()),
                'thread_id' => $email->getThreadId(),
                'processed_at' => $email->getProcessedAt() ? $email->getProcessedAt()->format('Y-m-d H:i:s') : null,
                'created_at' => $now,
                'updated_at' => $now,
                'read_at' => $email->getReadAt() ? $email->getReadAt()->format('Y-m-d H:i:s') : null,
                'account_id' => $email->getAccountId(),
                'origin' => $email->getOrigin() ? $email->getOrigin()->value : null,
                'external_id' => $email->getExternalId(),
                'deleted' => $email->getDeleted(),
                'failed' => $email->getFailed(),
                'reply_to' => $email->getData()->getReplyTo()
            ]
        );

        $email_tokens = EmailSearchTokens::validateAndCreate([
            'email_id' => $email->getId(),
            'params' => [
                'from' => $email->getData()->getFrom(),
                'to' => $email->getData()->getTo(),
                'cc' => $email->getData()->getCc(),
                'bcc' => $email->getData()->getBcc(),
                'subject' => $email->getData()->getSubject(),
                'body' => $email->getData()->getBody(),
            ]
        ]);

        $this->saveSearchTokens($email_tokens);
    }

    public function findById(string $id): ?Email
    {
        $data = DB::table('emails')
        ->where('id', $id)
        ->first();

        if (!$data) {
            return null;
        }

        return $this->map($data);
    }

    public function list(EmailFilter $filter): array
    {
        $query = DB::table('emails');

        if ($filter->direction) {
            $query->where('direction', $filter->direction->value);
        }

        if ($filter->folder_id) {
            $query->where('folder_id', $filter->folder_id);
        }

        if ($filter->read_start_date) {
            $query->where('read_at', '>=', $filter->read_start_date);
        }

        if ($filter->read_end_date) {
            $query->where('read_at', '<=', $filter->read_end_date);
        }

        if ($filter->process_start_date) {
            $query->where('processed_at', '>=', $filter->process_start_date);
        }

        if ($filter->process_end_date) {
            $query->where('processed_at', '<=', $filter->process_end_date);
        }

        if ($filter->accounts) {
            $query->whereIn('account_id', $filter->accounts);
        }

        if ($filter->query_email_address) {
            $query->where(function ($q) use ($filter) {
                $fields = $filter->query_email_address_fields ?: ['from', 'to', 'cc', 'bcc'];

                foreach ($fields as $field) {
                    $q->orWhere(function ($q2) use ($filter, $field) {
                        foreach ($filter->query_email_address as $email) {
                            $q2->orWhere($field, 'ilike', '%' . $email . '%');
                        }
                    });
                }
            });
        }

        // Paginação
        $query->skip(($filter->page - 1) * $filter->limit_per_page)
            ->take($filter->limit_per_page);

        $rawResults = $query->get();

        // Mapear os resultados em entidades
        return $rawResults->map(function ($doc) {
            return $this->map($doc);
        })->all();
    }

    public function findByAccount(string $accountId, array $filters, PaginationData $paginationData): PaginatedEmailsData
    {
        $query = DB::table('emails', 'e')
            ->select('e.*')
            ->join('email_search_tokens as est', 'e.id', '=', 'est.email_id')
            ->where('e.account_id', $accountId);


        foreach($filters as [$filter, $value]) {
            $query = $filter->apply($query, $value);
        }

        $query->distinct();

        $emails = $query->paginate(
            perPage: $paginationData->perPage,
            columns: ['*'],
            page: $paginationData->page
        );

        if ($emails->isEmpty()) {
            return PaginatedEmailsData::validateAndCreate([
                'items' => [],
                'total' => 0,
                'currentPage' => $emails->currentPage(),
                'perPage' => $emails->perPage()
            ]);
        }

        $mapped_emails = $emails->getCollection()
            ->map(fn ($email) => $this->map($email))
            ->all();

        return PaginatedEmailsData::validateAndCreate([
            'items' => $mapped_emails,
            'total' => $emails->total(),
            'currentPage' => $emails->currentPage(),
            'perPage' => $emails->perPage()
        ]);
    }

    public function findByClient(string $clientDomain, array $filters, PaginationData $pagination): PaginatedEmailsData
    {
        $query = DB::table('emails', 'e')
            ->select('e.*')
            ->join('email_search_tokens as est', 'e.id', '=', 'est.email_id')
            ->join('accounts as a', 'e.account_id', '=', 'a.id')
            ->where('a.email_address', 'ILIKE', "%@$clientDomain");

        foreach($filters as [$filter, $value]) {
            $query = $filter->apply($query, $value);
        }

        $query->distinct();

        $emails = $query->paginate(
            perPage: $pagination->perPage,
            columns: ['*'],
            page: $pagination->page
        );

        if ($emails->isEmpty()) {
            return PaginatedEmailsData::validateAndCreate([
                'items' => [],
                'total' => 0,
                'currentPage' => $emails->currentPage(),
                'perPage' => $emails->perPage()
            ]);
        }

        $mapped_emails = $emails->getCollection()
            ->map(fn ($email) => $this->map($email))
            ->all();

        return PaginatedEmailsData::validateAndCreate([
            'items' => $mapped_emails,
            'total' => $emails->total(),
            'currentPage' => $emails->currentPage(),
            'perPage' => $emails->perPage()
        ]);
    }

    function saveToken(string $email_id, string $type, $value)
    {
        DB::table('email_search_tokens')->insert([
            'email_id' => $email_id,
            'type' => $type,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    public function saveSearchTokens(EmailSearchTokens $email_tokens): void
    {


        $params = $email_tokens->params;
        foreach ($params as $type => $value) {
            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                continue;
            }

            if (!is_array($value)) {
                $this->saveToken($email_tokens->email_id, $type, $value);
                continue;
            }

            foreach ($value as $array_item) {
                $this->saveToken($email_tokens->email_id, $type, $array_item);
            }
        }
    }
}
