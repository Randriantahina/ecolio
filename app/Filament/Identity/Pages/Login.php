<?php

declare(strict_types=1);

namespace App\Filament\Identity\Pages;

use App\Application\Identity\Services\AuthService;
use App\Domain\Identity\Exceptions\AccountLockedException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

/**
 * Same credential rules as the JSON API (RG-A3 lockout, identical error for unknown account and wrong
 * password): the check is delegated to AuthService instead of Filament's own guard attempt.
 */
final class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('E-mail ou téléphone')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            // Per-IP throttle only; the per-account lockout lives in AuthService.
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        try {
            $user = app(AuthService::class)->authenticate($data['login'], $data['password']);
        } catch (InvalidCredentialsException) {
            $this->fail(__('auth.failed'));
        } catch (AccountLockedException) {
            $this->fail(__('auth.locked'));
        }

        if (! $user->canAccessPanel(Filament::getCurrentPanel())) {
            // Same message as a wrong password: do not reveal that the account exists but is not staff.
            $this->fail(__('auth.failed'));
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        return app(LoginResponse::class);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['data.login' => $message]);
    }
}
