<?php

namespace App\Http\Master\Controllers;

use App\Data\SaveEmailInputData;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailComplementDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaveEmailFromIdleController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private ClientRepository $clientRepository,
        private EmailRepository $emailRepository,
        private FolderRepository $folderRepository,
        private EmailComplementService $emailComplementService,
        private EmailComplementRepository $emailComplementRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $email_account = $request->input('email_account');

            if (!$email_account) {
                throw new \Exception('Email account is required');
            }

            // Busca a conta
            $account = $this->accountRepository->findByEmail($email_account);
            if (!$account) {
                throw new \Exception('Account not found');
            }

            // Busca o cliente pelo domínio do email da conta
            $domain = $this->extractDomain($email_account);
            $client = $domain ? $this->clientRepository->findByDomain($domain) : null;

            // Verifica se email já existe
            $email = $this->emailRepository->findByExternalId($request->input('external_id'));
            if ($email) {
                return response()->json([
                    'message' => 'Email already exists',
                ], 200);
            }

            $saveEmailInput = SaveEmailInputData::validateAndCreate([
                'account' => $account,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'cc' => $request->input('cc'),
                'bcc' => $request->input('bcc'),
                'subject' => $request->input('subject'),
                'body' => $request->input('body'),
                'thread_id' => $request->input('thread_id'),
                'attachments' => $request->input('attachments') ?? [],
                'reply_to' => $request->input('reply_to'),
                'external_id' => $request->input('external_id'),
                'processed_at' => $request->input('processed_at'),
                'complements' => $request->input('complements')
            ]);

            $folder = $this->folderRepository->findBySlug(Folder::INBOX->value);
            if (!$folder) {
                throw new \Exception('Folder not found');
            }

            $email = Email::create(
                account_id: $account->getId(),
                from: $saveEmailInput->from,
                to: $saveEmailInput->to,
                cc: $saveEmailInput->cc,
                bcc: $saveEmailInput->bcc,
                subject: $saveEmailInput->subject,
                body: $saveEmailInput->body,
                direction: Direction::INCOMING,
                folder_id: $folder->getId(),
                attachments: $saveEmailInput->hasAttachments(),
                reply_to: $saveEmailInput->reply_to,
                thread_id: $saveEmailInput->thread_id,
                external_id: $saveEmailInput->external_id,
                read: false,
                processed_at: $saveEmailInput->processed_at
            );

            $this->emailRepository->save($email);

            // Processa complements apenas se houver cliente
            if ($client) {
                try {
                    $complements = json_decode('{"copia": "", "modulo": "", "status": 0, "problema": "", "resposta": "", "resolvido": 0, "atualizado": "", "data_email": "", "importante": "", "respondido": "", "id_controle": "", "codigo_email": "", "id_categoria": "", "cod_encadeado": "", "data_resposta": "", "exige_resposta": "", "id_requisitado": "", "quem_respondeu": "", "controle_interno": "", "id_quem_respondeu": "", "quem_confirmo_exclusao": ""}');
                    
                    if ($saveEmailInput->complements) {
                        foreach ($saveEmailInput->complements as $key => $value) {
                            $complements->{$key} = $value;
                        }
                    }

                    $resolved_complements = $this->emailComplementService->applyTemplateAndSave(
                        complements: $complements,
                        client_id: $client->getId()
                    );

                    $email_complements = EmailComplementDTO::validateAndCreate([
                        'complements' => $resolved_complements,
                        'email_id' => $email->getId()
                    ]);

                    $this->emailComplementRepository->save($email_complements);
                } catch (\Exception $e) {
                    // Se não houver template, continua sem complements
                    Log::warning("Email complement template not found for client {$client->getId()}: " . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Email saved successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error saving email: ' . $th->getMessage(),
                'error'   => $th->getMessage(),
            ], 400);
        }
    }

    private function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? null;
    }
}

