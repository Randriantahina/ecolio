<?php

declare(strict_types=1);

namespace Modules\Absences\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Absences\Domain\Models\Absence;

interface AbsenceRepository
{
    /** @return Collection<int, Absence> */
    public function forClasseDate(int $ecoleId, int $classeId, string $date, string $periode): Collection;

    public function save(Absence $absence): void;

    public function delete(Absence $absence): void;

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Absence;
}
