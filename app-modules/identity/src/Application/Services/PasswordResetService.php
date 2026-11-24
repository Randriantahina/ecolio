<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Illuminate\Contracts\Auth\PasswordBroker;
use Modules\Identity\Domain\Exceptions\InvalidResetTokenException;
use Modules\Identity\Domain\Models\Utilisateur;

final class PasswordResetService
{
    public function __construct(private readonly PasswordBroker $broker) {}

    /** The broker status is deliberately dropped: the caller must not learn whether the e-mail exists. */
    public function envoyerLien(string $email): void
    {
        $this->broker->sendResetLink(['email' => $email]);
    }

    public function reinitialiser(string $email, string $token, string $password): void
    {
        $status = $this->broker->reset(
            ['email' => $email, 'token' => $token, 'password' => $password, 'password_confirmation' => $password],
            function (Utilisateur $user) use ($password): void {
                $user->mot_de_passe = $password;
                $user->reinitialiserEchecs();
                $user->save();
                // The old password may be the reason for the reset: every existing session must die with it.
                $user->tokens()->delete();
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw InvalidResetTokenException::create();
        }
    }
}
