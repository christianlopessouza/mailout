<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\ListEmailsByThreadIdInputData;
use App\Http\Controller;
use App\Http\Presenters\EmailPresenter;
use App\UseCases\ListEmailsByThreadId;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListEmailsByThreadIdController implements Controller
{
    public function __construct(
        private ListEmailsByThreadId $listEmailsByThreadId
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = ListEmailsByThreadIdInputData::from([
                'thread_id' => $request->route('thread_id')
            ]);

            $emails = $this->listEmailsByThreadId->execute($input);

            // Sempre retorna um array, mesmo que vazio
            $emails_formatted = array_map(function ($email) {
                return EmailPresenter::present($email);
            }, $emails);

            return response()->json([
                'emails' => $emails_formatted
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
