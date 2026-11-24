<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Contracts;

use App\Domain\Scolarite\Models\AnneeScolaire;
use Illuminate\Support\Collection;

interface AnneeScolaireRepository
{
    /** Newest first. A school has a handful of years, so no pagination. @return Collection<int, AnneeScolaire> */
    public function listForEcole(int $ecoleId): Collection;

    public function save(AnneeScolaire $annee): void;

    /** Throws ModelNotFoundException for another school's year so existence is never revealed. */
    public function findInEcoleOrFail(string $uuid, int $ecoleId): AnneeScolaire;

    public function currentOfEcole(int $ecoleId): ?AnneeScolaire;

    public function ecoleHasCurrent(int $ecoleId): bool;

    /** Clears the current flag of every year of the school (the new current is set right after). */
    public function clearCurrent(int $ecoleId): void;
}
