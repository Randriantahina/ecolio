<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Identity\Domain\Contracts\UtilisateurRepository;
use Modules\Identity\Domain\Models\Utilisateur;

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

    public function paginateForEcole(int $ecoleId, int $perPage): CursorPaginator
    {
        // Utilisateur is not tenant-scoped (login needs it), so the school filter must be explicit here.
        return Utilisateur::query()
            ->where('ecole_id', $ecoleId)
            ->with('roles')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function findInEcoleOrFail(string $uuid, int $ecoleId): Utilisateur
    {
        return Utilisateur::query()
            ->where('ecole_id', $ecoleId)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
