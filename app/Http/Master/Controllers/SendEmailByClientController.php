<?php

namespace App\Http\Master\Controllers;

use App\Data\AttachmentData;
use App\Data\Input\SendEmailByClientInputData;
use App\UseCases\SaveEmailComplement;
use App\UseCases\SendEmailByClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendEmailByClientController
{
    public function __construct(
        private SendEmailByClient $sendEmailByClientOutput,
        private SaveEmailComplement $saveEmailComplement
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $client = $request->attributes->get('client');
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

            $input = SendEmailByClientInputData::validateAndCreate([
                'client'=> $client,
                'email' => [
                    'from' => $request->input('from'),
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
                    'external_id' => $request->input('external_id'),
                ]
            ]);

            $sendEmailByClientOutput = $this->sendEmailByClientOutput->execute($input);
            return response()->json([
                'message' => 'Mail sent successfully, complement and template saved',
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}