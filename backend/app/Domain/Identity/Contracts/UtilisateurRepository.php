<?php

declare(strict_types=1);

namespace App\Domain\Identity\Contracts;

use App\Domain\Identity\Models\Utilisateur;

interface UtilisateurRepository
{
    /** Resolves an account by e-mail or phone; phone matching ignores spaces. */
    public function findByLogin(string $login): ?Utilisateur;

    public function save(Utilisateur $utilisateur): void;
}
