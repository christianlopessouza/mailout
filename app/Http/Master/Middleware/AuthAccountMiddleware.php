<?php

namespace App\Http\Master\Middleware;

use App\Errors\UnauthorizedDomainError;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use Closure;
use Illuminate\Http\Request;

class AuthAccountMiddleware
{
    public function __construct(
        private AccountRepository $accountRepository,
        private ClientRepository $clientRepository
    ) {}
    public function handle(Request $request, Closure $next)
    {
        $client_token = $request->bearerToken();
        $account_token = $request->input('account');

        if (!$client_token || !$account_token) {
            return response()->json(['message' => 'Missing authorization params'], 401);
        }

        $client = $this->clientRepository->findByToken($client_token);
        if (!$client) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $account = $this->accountRepository->findByToken($account_token);
        if (!$account) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (strpos($account->getEmailAddress(), '@' . $client->getDomain()) === false) {
            return response()->json(['message' => 'Unauthorized domain'], 401);
        }

        if (!$account) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->attributes->set('account', $account);

        return $next($request);
    }
}
