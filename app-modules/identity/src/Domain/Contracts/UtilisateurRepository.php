<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Identity\Domain\Models\Utilisateur;

interface UtilisateurRepository
{
    /** Resolves an account by e-mail or phone; phone matching ignores spaces. */
    public function findByLogin(string $login): ?Utilisateur;

    public function save(Utilisateur $utilisateur): void;

    /** @return CursorPaginator<Utilisateur> */
    public function paginateForEcole(int $ecoleId, int $perPage): CursorPaginator;

    /** Throws ModelNotFoundException for another school's user so existence is never revealed. */
    public function findInEcoleOrFail(string $uuid, int $ecoleId): Utilisateur;
}
