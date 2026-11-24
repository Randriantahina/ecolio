<?php

declare(strict_types=1);

namespace Modules\Absences\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Absences\Domain\Models\Absence;

interface AbsenceRepository
{
    /** @return Collection<int, Absence> */
    public function forClasseDate(int $ecoleId, int $classeId, string $date, string $periode): Collection;

    /** @return array<string,int> Y-m-d => number of absence rows (days without any are missing). */
    public function countParJour(int $ecoleId, string $from, string $to): array;

    /** Throws ModelNotFoundException for another school's absence. */
    public function findOrFail(int $id, int $ecoleId): Absence;

    public function save(Absence $absence): void;

    public function delete(Absence $absence): void;

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Absence;
}
