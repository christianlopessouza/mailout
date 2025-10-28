<?php

namespace App\Http\Master\Controllers;

use App\Data\AttachmentData;
use App\Data\SaveEmailInputData;
use App\Domain\Entities\Attachment;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Folder;
use App\Domain\Enums\AttachmentStatus;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\AttachmentService;
use App\Infrastructure\Services\EmailComplementService;
use App\Infrastructure\Persistence\AttachmentRepository;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\EmailComplementDTO;
use App\UseCases\SaveEmailComplement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveEmailController
{
    public function __construct(
        private SaveEmailComplement $saveEmailComplement,
        private AccountRepository $accountRepository,
        private AttachmentService $attachmentService,
        private AttachmentRepository $attachmentRepository,
        private EmailComplementService $emailComplementService,
        private EmailComplementRepository $emailComplementRepository,
        private EmailRepository $emailRepository,
        private FolderRepository $folderRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var \App\Domain\Entities\Client $client */
            $client = $request->attributes->get('client');

            $email_account = $request->input('email_account');

            if (!$email_account) {
                throw new \Exception('Email account is required');
            }

            $account = $this->accountRepository->findByEmail($email_account);

            if (!$account) {
                throw new \Exception('Account not found');
            }

            $email = $this->emailRepository->findByExternalId($request->input('external_id'));
            if ($email) {
                throw new \Exception('Email already exists');
            }

            $attachments = [];

            if (!!$request->file('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = AttachmentData::validateAndCreate([
                        'filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'path' => $file->getPathname(),
                    ])->toArray();
                }
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
                'attachments' => $attachments,
                'reply_to' => $request->input('reply_to'),
                'external_id' => $request->input('external_id'),
                'processed_at' => $request->input('processed_at'),
                'complements' =>  (object) $request->input('complements')
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

            if ($saveEmailInput->hasAttachments()) {
                foreach ($saveEmailInput->attachments as $attachment_input) {
                    $attachment = Attachment::create(
                        filename: $attachment_input->filename,
                        mimetype: $attachment_input->mime_type,
                        size: $attachment_input->size,
                        status: AttachmentStatus::RECEIVED,
                        email_id: $email->getId(),
                        attachable_id: $attachment_input->attachable_id ?? null
                    );

                    $this->attachmentService->store(
                        filepath: $attachment_input->path,
                        attachment: $attachment
                    );

                    $this->attachmentRepository->save($attachment);
                }
            }

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
}
