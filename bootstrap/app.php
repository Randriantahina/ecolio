<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Eleves\Domain\Exceptions\DuplicateEleveException;
use Modules\Identity\Domain\Exceptions\AccountLockedException;
use Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use Modules\Identity\Domain\Exceptions\InvalidResetTokenException;
use Modules\Scolarite\Domain\Exceptions\InvalidAnneeScolaireException;
use Modules\Tenancy\Http\Middleware\ResolveTenant;

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
        // The JSON API owns these status codes; Filament actions catch the same exceptions themselves.

        $exceptions->render(fn (InvalidCredentialsException $e) => response()->json(['message' => __('auth.failed'), 'errors' => ['login' => [__('auth.failed')]]], 422));

        $exceptions->render(fn (InvalidResetTokenException $e) => response()->json([
            'message' => __('auth.reset_invalid'),
            'errors' => ['token' => [__('auth.reset_invalid')]],
        ], 422));

        $exceptions->render(fn (AccountLockedException $e) => response()->json(['message' => __('auth.locked')], 423));

        $exceptions->render(fn (InvalidAnneeScolaireException $e) => response()->json(['message' => $e->getMessage(), 'errors' => ['date_fin' => [$e->getMessage()]]], 422));

        // RG-E4: a blocking warning, not a failure; the client re-sends with confirmer_doublon.
        $exceptions->render(fn (DuplicateEleveException $e) => response()->json([
            'message' => 'Un élève avec le même nom et la même date de naissance existe déjà.',
            'code' => 'doublon_eleve',
            'existant' => ['uuid' => $e->existant->uuid, 'matricule' => $e->existant->matricule, 'nom' => $e->existant->nom],
        ], 409));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
