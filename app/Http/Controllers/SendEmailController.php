<?php

namespace App\Http\Controllers;

use App\Data\SendEmailInputData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UseCases\SendEmail;
use Illuminate\Http\JsonResponse;

class SendEmailController implements Controller
{
    public function __construct(
        private SendEmail $sendEmail
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $account = $request->attributes->get('account');

            $send_email_input = SendEmailInputData::validateAndCreate([
                'account' => $account,
                'email_data' => [
                    'to' => $request->input('to'),
                    'cc' => $request->input('cc'),
                    'bcc' => $request->input('bcc'),
                    'subject' => $request->input('subject'),
                    'body' => $request->input('body'),
                    'attachments' => $request->input('attachments')
                ]
            ]);

            $this->sendEmail->execute($send_email_input);

            return response(200)->json([
                'message' => 'Mail sent successfully'
            ]);
        } catch (\Throwable $th) {
            return response(400)->json([
                'message' => $th->getMessage()
            ]);
        }
    }
}
