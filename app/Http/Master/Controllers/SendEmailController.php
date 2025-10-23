<?php

namespace App\Http\Master\Controllers;

use App\Data\AttachmentData;
use App\Data\Input\SenddEmailInputData;
use App\Data\Input\SendEmailInputData;
use App\UseCases\SendEmail;
use App\UseCases\SaveEmailComplement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendEmailController
{
    public function __construct(
        private SendEmail $sendEmail,
        private SaveEmailComplement $saveEmailComplement
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $account = $request->attributes->get('account');
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
            // Recebe os dados do e-mail
            $input = SendEmailInputData::validateAndCreate([
                'account' => $account,
                'email' => [
                    'to' => $request->input('to'),
                    'cc' => $request->input('cc'),
                    'bcc' => $request->input('bcc'),
                    'subject' => $request->input('subject'),
                    'body' => $request->input('body'),
                    'attachments' => $attachments,
                    'origin' => $request->input('origin'),
                    'complements' => $request->input('complement'),
                    'reply_to' => $request->input('reply_to'),
                    'thread_id' => $request->input('thread_id'),
                ]
            ]);

            // foreach ($attachments as $attachment) {
            //     file_put_contents(
            //         __DIR__ . '/../../../storage/' . $attachment['filename'],
            //         file_get_contents($attachment['path'])
            //     );
            // }

            // Envia o e-mail (metodologia já existente)
            $sendEmailOutput = $this->sendEmail->execute($input);
            // Obtém o ID do e-mail diretamente do Email (não do SendEmailOutputData)
            $emailId = $sendEmailOutput->email->getId();  // Aqui obtemos o ID do Email diretamente
            return response()->json([
                'email_id' => $emailId,
            ], 200);
        } catch (\Exception $th) {
            \Log::error('SendEmailController error: ' . $th->getMessage(), [
                'exception' => $th,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => $th->getMessage(),
                'error_type' => get_class($th),
                'details' => $th->getTraceAsString()
            ], 400);
        }
    }
}
