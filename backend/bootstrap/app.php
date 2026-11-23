<?php

use App\Domain\Identity\Exceptions\AccountLockedException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use App\Domain\Identity\Exceptions\InvalidResetTokenException;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['tenant' => ResolveTenant::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain exceptions stay HTTP-agnostic; the status codes are decided here.
        $exceptions->render(fn (InvalidCredentialsException $e) => response()->json([
            'message' => __('auth.failed'),
            'errors' => ['login' => [__('auth.failed')]],
        ], 422));

        $exceptions->render(fn (InvalidResetTokenException $e) => response()->json([
            'message' => __('auth.reset_invalid'),
            'errors' => ['token' => [__('auth.reset_invalid')]],
        ], 422));

        $exceptions->render(fn (AccountLockedException $e) => response()->json([
            'message' => __('auth.locked'),
        ], 423));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
