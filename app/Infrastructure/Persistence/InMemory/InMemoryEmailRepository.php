<?php

namespace App\Infrastructure\Persistence\InMemory;

use App\Data\EmailFilter;
use App\Data\EmailSearchTokens;
use App\Domain\Entities\Email;
use App\Infrastructure\Persistence\EmailRepository;


class InMemoryEmailRepository implements EmailRepository
{
    /** @var Email[] */
    private array $data = [];
    public function save(Email $email): void
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
    }
    public function list(EmailFilter $filter): array
    {
        $emails = array_slice($this->data, $filter->page - 1, $filter->limit_per_page);
        $emails = array_filter($emails, function (Email $email) use ($filter) {
            return ($filter->direction == null || $email->getDirection() === $filter->direction) &&
                ($filter->folder_id == null || $email->getFolderId() === $filter->folder_id) &&
                ($filter->read_start_date == null || $email->getReadAt() >= $filter->read_start_date) &&
                ($filter->read_end_date == null || $email->getReadAt() <= $filter->read_end_date) &&
                ($filter->process_start_date == null || $email->getProcessedAt() >= $filter->process_start_date) &&
                ($filter->process_end_date == null || $email->getProcessedAt() <= $filter->process_end_date) &&
                ($filter->accounts == null || in_array($email->getAccountId(), $filter->accounts)) &&
                ($filter->query_email_address == null ||

                    in_array($email->getData()->getFrom(), $filter->query_email_address) ||
                    $this->findEmail($filter->query_email_address, $email->getData()->getTo()) ||
                    $this->findEmail($filter->query_email_address, $email->getData()->getCc()) ||
                    $this->findEmail($filter->query_email_address, $email->getData()->getBcc())

                );
        });

        return $emails;
    }

    private function findEmail(array $array, ?array $search): bool
    {
        if ($search === null) {
            return false;
        }
        return !empty(array_intersect($array, $search));
    }

    public function saveSearchTokens(EmailSearchTokens $emailTokens): void
    {
        // 1) Construa um array type => value. Se algum valor for array, converta para JSON.
        $todos = [
            'from' => $emailTokens->from,
            'to' => $emailTokens->to,
            'cc' => $emailTokens->cc,
            'bcc' => $emailTokens->bcc,
            'subject' => $emailTokens->subject,
            'body' => $emailTokens->body,
        ];

        $now = now(); // preenche created_at e updated_at

        foreach ($todos as $type => $value) {
            // 1) Pule valores nulos ou strings vazias
            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                continue;
            }

            // 2) Se for um array (cc, bcc), ou outro tipo que não queira, transforme em string
            //    — Neste exemplo, forçamos qualquer array a virar uma string de e-mails separados por vírgula.
            if ($type === 'to') {
                $valorParaSalvar = is_array($value) ? implode(',', $value) : $value;
            } elseif ($type === 'cc' || $type === 'bcc') {
                // se quiser mantê-los como JSON:
                $valorParaSalvar = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $valorParaSalvar = $value;
            }
        }
    }

}
