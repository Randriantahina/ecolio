<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Application\Identity\DTOs\AuthResult;
use App\Application\Identity\DTOs\LoginDto;
use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Exceptions\AccountLockedException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    /** RG-A3: long-lived token so a power or network cut does not force a new login. */
    private const TOKEN_DAYS = 30;

    /** Burned when the account is unknown so response time does not reveal which logins exist. */
    private const DUMMY_HASH = '$2y$12$4q09H6lRzbzdcsN1yPRbxO1YPiaZnqFt59kVUJCGtgYYLuaiwGQ0O';

    public function __construct(private readonly UtilisateurRepository $utilisateurs) {}

    /** Shared by the token API and the session login so the lockout rules can never diverge. */
    public function authenticate(string $login, string $password): Utilisateur
    {
        $user = $this->utilisateurs->findByLogin($login);

        if ($user === null || ! $user->actif) {
            Hash::check($password, self::DUMMY_HASH);

            throw InvalidCredentialsException::create();
        }

        // Checked before the password: otherwise an attacker keeps guessing during the lock.
        if ($user->estVerrouille()) {
            throw AccountLockedException::create();
        }

        if (! Hash::check($password, $user->mot_de_passe)) {
            $user->enregistrerEchec();
            $this->utilisateurs->save($user);

            throw InvalidCredentialsException::create();
        }

        $user->reinitialiserEchecs();
        $user->derniere_connexion = now();
        $this->utilisateurs->save($user);

        return $user;
    }

    public function login(LoginDto $dto): AuthResult
    {
        $user = $this->authenticate($dto->login, $dto->password);

        $token = $user->createToken($dto->deviceName, ['*'], now()->addDays(self::TOKEN_DAYS));

        return new AuthResult($token->plainTextToken, $user);
    }
}
