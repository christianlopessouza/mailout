<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\ListEmailsByAccountInputData;
use App\Http\Controller;
use App\Http\Presenters\EmailPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\UseCases\ListEmailsByAccount;

class ListEmailsByAccountController implements Controller
{
    public function __construct(
        private ListEmailsByAccount $listEmailsByAccount
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $account = $request->attributes->get('account');
            $direction = $request->input('direction');
            $read = $request->input('read');
            $folder_id = $request->input('folder');
            $process_start_date = $request->input('process_start_date');
            $process_end_date = $request->input('process_end_date');
            $read_start_date = $request->input('read_start_date');
            $read_end_date = $request->input('read_end_date');
            $query_email_address = $request->input('query_email_address');
            $query_email_address_fields = $request->input('query_email_address_fields');
            $order = $request->input('order');
            $limit = $request->input('limit');
            $page = $request->input('page');
            $input = ListEmailsByAccountInputData::validateAndCreate([
                'account' => $account,
                'filter' => [
                    'direction' => $direction,
                    'read' => $read,
                    'folder_id' => $folder_id,
                    'process_start_date' => $process_start_date,
                    'process_end_date' => $process_end_date,
                    'read_start_date' => $read_start_date,
                    'read_end_date' => $read_end_date,
                    'order' => $order,
                    'query_email_address' => $query_email_address,
                    'limit' => $limit,
                    'page' => $page,
                    'query_email_address_fields' => $query_email_address_fields
                ]
            ]);

            $email_list = $this->listEmailsByAccount->execute($input);
            $email_list_formatted = array_map(function ($email) {
                return EmailPresenter::present($email);
            }, $email_list);

            return response()->json([
                'emails' => $email_list_formatted
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
