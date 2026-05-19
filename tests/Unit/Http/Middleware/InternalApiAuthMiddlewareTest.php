<?php

use App\Http\Master\Middleware\AuthInternalMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['services.internal_api_token' => 'internal-test-token']);
});

it('rejects requests without an internal token', function () {
    $middleware = new AuthInternalMiddleware();
    $request = Request::create('/api/accounts/active', 'GET');

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    expect($response->getData(true))->toMatchArray(['message' => 'Invalid internal token']);
});

it('rejects requests with an invalid bearer token', function () {
    $middleware = new AuthInternalMiddleware();
    $request = Request::create('/api/accounts/active', 'GET', server: [
        'HTTP_AUTHORIZATION' => 'Bearer wrong-token',
    ]);

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
});

it('allows requests with a valid bearer token', function () {
    $middleware = new AuthInternalMiddleware();
    $request = Request::create('/api/accounts/active', 'GET', server: [
        'HTTP_AUTHORIZATION' => 'Bearer internal-test-token',
    ]);

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    expect($response->getData(true))->toMatchArray(['ok' => true]);
});

it('allows requests with a valid internal token header', function () {
    $middleware = new AuthInternalMiddleware();
    $request = Request::create('/api/internal/save-email', 'POST', server: [
        'HTTP_X_INTERNAL_TOKEN' => 'internal-test-token',
    ]);

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(Response::HTTP_OK);
});

it('protects worker-only routes with internal auth middleware', function () {
    $activeAccountsRoute = Route::getRoutes()->getByName('internal.accounts.active');
    $saveEmailRoute = Route::getRoutes()->getByName('internal.save-email');

    expect($activeAccountsRoute?->middleware())->toContain('auth.internal');
    expect($saveEmailRoute?->middleware())->toContain('auth.internal');
});
