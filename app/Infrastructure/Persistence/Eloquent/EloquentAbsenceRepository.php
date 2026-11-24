<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Absences\Contracts\AbsenceRepository;
use App\Domain\Absences\Models\Absence;
use Illuminate\Support\Collection;

final class EloquentAbsenceRepository implements AbsenceRepository
{
    public function forClasseDate(int $ecoleId, int $classeId, string $date, string $periode): Collection
    {
        return Absence::query()->with('eleve')->where('ecole_id', $ecoleId)->where('classe_id', $classeId)
            ->whereDate('date', $date)->where('periode', $periode)->get();
    }

    public function save(Absence $absence): void
    {
        $absence->save();
    }

    public function delete(Absence $absence): void
    {
        $absence->delete();
    }

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Absence
    {
        return Absence::query()->where('ecole_id', $ecoleId)->where('client_uuid', $clientUuid)->first();
    }
}
