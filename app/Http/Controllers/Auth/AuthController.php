<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\Identity\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UtilisateurResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->toDto());

        return response()->json([
            'token' => $result->token,
            'utilisateur' => UtilisateurResource::make($result->utilisateur->load('ecole'))->resolve(),
        ]);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        // Only this device's token: logging out on the phone must not kick the desktop session.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'utilisateur' => UtilisateurResource::make($request->user()->load('ecole'))->resolve(),
        ]);
    }
}
