<?php

namespace App\Http\Master\Controllers;

use App\Domain\Entities\Email;
use App\Infrastructure\Persistence\EmailRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateEmailController
{
    public function __construct(
        private EmailRepository $emailRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $id = $request->id;

            $email = $this->emailRepository->findById($id);
            if (!$email) {
                throw new \Exception('Email not found');
            }

            // Campos editáveis do Email
            $account_id = $request->has('account_id') ? $request->input('account_id') : $email->getAccountId();
            $folder_id = $request->has('folder_id') ? $request->input('folder_id') : $email->getFolderId();
            $external_id = $request->has('external_id') ? $request->input('external_id') : $email->getExternalId();
            $deleted = $request->has('deleted') ? $request->input('deleted') : $email->getDeleted();
            $failed = $request->has('failed') ? $request->input('failed') : $email->getFailed();
            $read = $request->has('read') ? $request->input('read') : $email->getRead();
            
            // Processar read_at
            $read_at = null;
            if ($request->has('read_at')) {
                $read_at_input = $request->input('read_at');
                if ($read_at_input) {
                    $read_at = is_string($read_at_input) ? new \DateTime($read_at_input) : $read_at_input;
                }
            } else {
                $read_at = $email->getReadAt();
            }
            
            // Processar processed_at
            $processed_at = null;
            if ($request->has('processed_at')) {
                $processed_at_input = $request->input('processed_at');
                if ($processed_at_input) {
                    $processed_at = is_string($processed_at_input) ? new \DateTime($processed_at_input) : $processed_at_input;
                }
            }

            // Preparar dados para update
            $updateData = [];
            
            if ($request->has('account_id')) {
                $updateData['account_id'] = $account_id;
            }
            
            if ($request->has('folder_id')) {
                $updateData['folder_id'] = $folder_id;
            }
            
            if ($request->has('external_id')) {
                $updateData['external_id'] = $external_id;
            }
            
            if ($request->has('deleted')) {
                $updateData['deleted'] = $deleted;
            }
            
            if ($request->has('failed')) {
                $updateData['failed'] = $failed;
            }
            
            if ($request->has('read')) {
                $updateData['read'] = $read;
            }
            
            if ($request->has('read_at')) {
                $updateData['read_at'] = $read_at;
            }
            
            if ($request->has('processed_at')) {
                $updateData['processed_at'] = $processed_at;
            }

            // Atualizar diretamente no banco
            $this->emailRepository->update($id, $updateData);

            // Buscar o email atualizado para retornar
            $updatedEmail = $this->emailRepository->findById($id);

            return response()->json([
                'message' => 'Email updated successfully',
                'email' => $updatedEmail->toArray()
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}

