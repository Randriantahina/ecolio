<?php

declare(strict_types=1);

namespace App\Domain\Absences\Contracts;

use App\Domain\Absences\Models\Absence;
use Illuminate\Support\Collection;

interface AbsenceRepository
{
    /** @return Collection<int, Absence> */
    public function forClasseDate(int $ecoleId, int $classeId, string $date, string $periode): Collection;

    public function save(Absence $absence): void;

    public function delete(Absence $absence): void;

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Absence;
}
