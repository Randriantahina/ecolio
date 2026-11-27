<?php

declare(strict_types=1);

namespace Modules\Eleves\Domain\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Eleves\Domain\Models\Eleve;

interface EleveRepository
{
    /**
     * Ordered by matricule (the registry is a matricule-sorted index, spec 0.1).
     *
     * @param  array{q?:?string,classe?:?string,statut?:?string}  $filtres
     * @return CursorPaginator<Eleve>
     */
    public function paginate(int $ecoleId, array $filtres, int $perPage): CursorPaginator;

    /** Throws ModelNotFoundException for another school's pupil so existence is never revealed. */
    public function findOrFail(string $uuid, int $ecoleId): Eleve;

    public function save(Eleve $eleve): void;

    /** @return array{actifs:int, sans_classe:int} Active pupils, and those among them without an active enrolment. */
    public function effectifs(int $ecoleId): array;

    public function findByMatricule(int $ecoleId, string $matricule): ?Eleve;

    public function matriculeExists(int $ecoleId, string $matricule, ?int $sauf = null): bool;

    /** RG-E4: same name, first names and birth date. */
    public function findDuplicate(int $ecoleId, string $nom, ?string $prenoms, ?string $dateNaissance): ?Eleve;
}
