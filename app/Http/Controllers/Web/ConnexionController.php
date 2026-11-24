<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Identity\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ConnexionController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Domain failures (wrong credentials, locked account) are turned into form errors in bootstrap/app.php.
        $user = $this->auth->authenticate((string) $request->validated('login'), (string) $request->validated('password'));

        Auth::login($user);
        // New session id at login: prevents session fixation.
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/connexion');
    }
}
