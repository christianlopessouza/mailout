<?php

namespace App\Http\Master\Controllers;

use App\Http\Controller;
use Illuminate\Http\Request;
use App\UseCases\SendEmail;
use Illuminate\Http\JsonResponse;

class TestController implements Controller
{
    public function __construct(
        private SendEmail $sendEmail
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Mail sent successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
