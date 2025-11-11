<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\ListEmailByIdInputData;
use App\Http\Controller;
use App\Http\Presenters\EmailPresenter;
use App\UseCases\ListEmailById;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListEmailByIdController implements Controller
{
    public function __construct(
        private ListEmailById $listEmailById
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = ListEmailByIdInputData::validateAndCreate([
                'id' => $request->id
            ]);

            $email = $this->listEmailById->execute($input);

            return response()->json(EmailPresenter::present($email), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
