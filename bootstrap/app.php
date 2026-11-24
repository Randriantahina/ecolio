<?php

use App\Domain\Eleves\Exceptions\DuplicateEleveException;
use App\Domain\Identity\Exceptions\AccountLockedException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use App\Domain\Identity\Exceptions\InvalidResetTokenException;
use App\Domain\Scolarite\Exceptions\InvalidAnneeScolaireException;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->redirectGuestsTo('/connexion');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain exceptions stay HTTP-agnostic; the status codes are decided here.
        // Pages (Inertia) get a redirect back with form errors; the JSON API keeps its status codes.
        $isApi = fn (Request $request): bool => $request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'));

        $exceptions->render(fn (InvalidCredentialsException $e, Request $request) => $isApi($request)
            ? response()->json(['message' => __('auth.failed'), 'errors' => ['login' => [__('auth.failed')]]], 422)
            : back()->withErrors(['login' => __('auth.failed')]));

        $exceptions->render(fn (InvalidResetTokenException $e) => response()->json([
            'message' => __('auth.reset_invalid'),
            'errors' => ['token' => [__('auth.reset_invalid')]],
        ], 422));

        $exceptions->render(fn (AccountLockedException $e, Request $request) => $isApi($request)
            ? response()->json(['message' => __('auth.locked')], 423)
            : back()->withErrors(['login' => __('auth.locked')]));

        $exceptions->render(fn (InvalidAnneeScolaireException $e, Request $request) => $isApi($request)
            ? response()->json(['message' => $e->getMessage(), 'errors' => ['date_fin' => [$e->getMessage()]]], 422)
            : back()->withErrors(['date_fin' => $e->getMessage()]));

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
