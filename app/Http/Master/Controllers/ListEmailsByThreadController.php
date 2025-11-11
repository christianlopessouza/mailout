<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\ListEmailsByThreadInputData;
use App\Http\Controller;
use App\Http\Presenters\EmailPresenter;
use App\UseCases\ListEmailsByThread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListEmailsByThreadController implements Controller
{
    public function __construct(
        private ListEmailsByThread $listEmailsByThread
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = ListEmailsByThreadInputData::from([
                'thread_id' => $request->route('thread_id')
            ]);

            $emails = $this->listEmailsByThread->execute($input);

            // Sempre retorna um array, mesmo que vazio
            $emails_formatted = array_map(function ($email) {
                return EmailPresenter::present($email);
            }, $emails);

            return response()->json([
                'emails' => $emails_formatted
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
