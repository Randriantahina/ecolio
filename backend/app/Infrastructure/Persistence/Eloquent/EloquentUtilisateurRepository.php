<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;

final class EloquentUtilisateurRepository implements UtilisateurRepository
{
    public function findByLogin(string $login): ?Utilisateur
    {
        // Phones are typed "034 11 222 33" or "0341122233" depending on who entered them.
        $compact = str_replace(' ', '', $login);

        return Utilisateur::query()
            ->where('email', $login)
            ->orWhereRaw("REPLACE(telephone, ' ', '') = ?", [$compact])
            ->first();
    }

    public function save(Utilisateur $utilisateur): void
    {
        $utilisateur->save();
    }
}
