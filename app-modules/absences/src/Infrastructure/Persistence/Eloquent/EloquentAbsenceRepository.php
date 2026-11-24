<?php

declare(strict_types=1);

namespace Modules\Absences\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Absences\Domain\Contracts\AbsenceRepository;
use Modules\Absences\Domain\Models\Absence;

final class EloquentAbsenceRepository implements AbsenceRepository
{
    public function forClasseDate(int $ecoleId, int $classeId, string $date, string $periode): Collection
    {
        return Absence::query()->with('eleve')->where('ecole_id', $ecoleId)->where('classe_id', $classeId)
            ->whereDate('date', $date)->where('periode', $periode)->get();
    }

    public function countParJour(int $ecoleId, string $from, string $to): array
    {
        return Absence::query()->where('ecole_id', $ecoleId)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->selectRaw('date, count(*) as total')->groupBy('date')->get()
            ->mapWithKeys(fn ($r): array => [substr((string) $r->date, 0, 10) => (int) $r->total])->all();
    }

    public function findOrFail(int $id, int $ecoleId): Absence
    {
        return Absence::query()->where('ecole_id', $ecoleId)->findOrFail($id);
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
