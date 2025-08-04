<?php

namespace App\Http\Master\Controllers;

use App\Data\EmailFilterData;
use App\Data\Input\FilterEmailsByClientInputData;
use App\Domain\Enums\Direction;
use App\Http\Controller;
use App\UseCases\FilterEmailsByClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterEmailsByClientController implements Controller
{
    public function __construct(
        private readonly FilterEmailsByClient $filterEmailsByClient
    ) {}

    public function __invoke(Request $request):JsonResponse
    {
        try {
            $client = $request->attributes->get('client');

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
            ]);

            $input = FilterEmailsByClientInputData::validateAndCreate([
                'client' => $client,
                'filter' => $filterData
            ]);

            return response()->json([
                'message' => 'This controller is not implemented yet.'
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
