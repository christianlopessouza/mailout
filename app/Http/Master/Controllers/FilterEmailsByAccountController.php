<?php

namespace App\Http\Master\Controllers;

use App\Data\EmailFilterData;
use App\Data\Input\FilterEmailsByAccountInputData;
use App\Domain\Enums\Direction;
use App\Http\Controller;
use App\UseCases\FilterEmailsByAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterEmailsByAccountController implements Controller
{
    public function __construct(
        private readonly FilterEmailsByAccount $filterEmailsByAccount
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $account = $request->attributes->get('account');

            $filterData = EmailFilterData::validateAndCreate([
                'folder_slug' => $request->input('folder_slug'),
                'process_start_date' => $request->input('date_from'),
                'process_end_date' => $request->input('date_to'),
                'read' => $request->input('read'),
                'read_start_date' => $request->input('read_date_from'),
                'read_end_date' => $request->input('read_date_to'),
                'subject_contains' => $request->input('subject_contains'),
                'body_contains' => $request->input('body_contains'),
                'email_address' => $request->input('email_address'),
                'direction' => Direction::tryFrom($request->input('direction')),
                'order_by' => $request->input('order_by'),
                'order' => $request->input('order'),
                'limit_per_page' => $request->input('limit_per_page'),
                'complements' => json_decode(json_encode($request->input('complements'))),
                'account_id' => $request->input('account_id'),
                'flag_names' => $request->input('flag_names'),
            ]);
            
            $input = FilterEmailsByAccountInputData::validateAndCreate([
                'account' => $account,
                'filter' => $filterData->toArray()
            ]);

            $result = $this->filterEmailsByAccount->execute($input);

            $emails = array_map(function($email) {
                return $email->toArray();
            }, $result->emails);

            return response()->json([
                'emails' => $emails,
                'total' => $result->total
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}