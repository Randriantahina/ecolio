<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\Identity\Services\PasswordResetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

final class PasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->envoyerLien($request->validated('email'));

        return response()->json(['message' => __('auth.reset_sent')]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwords->reinitialiser(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        return response()->json(['message' => __('auth.reset_done')]);
    }
}
