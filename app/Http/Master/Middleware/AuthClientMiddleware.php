<?php

namespace App\Http\Master\Middleware;

use App\Infrastructure\Persistence\ClientRepository;
use Closure;
use Illuminate\Http\Request;

class AuthClientMiddleware
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {}
    public function handle(Request $request, Closure $next)
    {
        $client_token = $request->bearerToken();

        if (!$client_token) {
            return response()->json(['message' => 'Missing authorization params'], 401);
        }

        $client = $this->clientRepository->findByToken($client_token);
        if (!$client) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->attributes->set('client', $client);

        return $next($request);
    }
}
