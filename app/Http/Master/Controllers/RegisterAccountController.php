<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\RegisterAccountInputData;
use App\Http\Controller;
use App\UseCases\RegisterAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterAccountController implements Controller
{
    public function __construct(
        private readonly RegisterAccount $registerAccount
    ) {}
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $email_address = $request->input('email_address');
            $password = $request->input('password');
            $host = $request->input('host');
            $port = $request->input('port');
            $username = $request->input('username');

            $input = RegisterAccountInputData::validateAndCreate([
                'email_address' => $email_address,
                'host' => $host,
                'port' => $port,
                'password' => $password,
                'username' => $username
            ]);

            $output = $this->registerAccount->execute($input);

            return response()->json([
                'message' => 'Account registered successfully',
                'data' => [
                    'access_token' => $output->account->getToken()
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to register account',
                'texto' => $th->getMessage()
            ], 400);
        }
    }
}
