<?php

namespace App\Http\Master\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListActiveAccountsController implements Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Busca contas ativas com senha
            $accounts = DB::table('accounts')
                ->whereNotNull('password')
                ->where('active', true)
                ->select([
                    'id',
                    'email_address',
                    'password',
                    'host',
                    'port',
                    'username',
                ])
                ->get();

            return response()->json($accounts->toArray(), 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to fetch accounts',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}

