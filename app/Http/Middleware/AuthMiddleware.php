<?php

namespace App\Http\Middleware;

use App\UseCases\Auth\AuthUseCaseInterface;
use App\UseCases\Auth\AuthUseCaseRequest;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\App;
use App\JWT;
use App\UseCases\AuthUseCase;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    private AuthUseCaseInterface $authUserUseCase;

    public function __construct(AuthUseCaseInterface $authUserUseCase)
    {
        $this->authUserUseCase = $authUserUseCase;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Token JWT não fornecido'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $secret = env('JWT_SECRET');
            $decoded = JWT::decode($token, $secret);

            $user = $this->authUserUseCase->execute(
                new AuthUseCaseRequest($decoded->usuario, $decoded->senha)
            )->getUser();

            $actualEmail = $decoded->emailAtual ?? $user->getDefaultEmail()->getEmail();
            $request->attributes->set('user', $user);
            $request->attributes->set('actualEmail', $actualEmail);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido: ' . $e->getMessage()], 401);
        }
    }
}