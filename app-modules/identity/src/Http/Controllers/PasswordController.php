<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Identity\Application\Services\PasswordResetService;
use Modules\Identity\Http\Requests\ForgotPasswordRequest;
use Modules\Identity\Http\Requests\ResetPasswordRequest;

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
