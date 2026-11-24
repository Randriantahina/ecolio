<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Scolarite\Contracts\AnneeScolaireRepository;
use App\Domain\Scolarite\Models\AnneeScolaire;
use Illuminate\Support\Collection;

final class EloquentAnneeScolaireRepository implements AnneeScolaireRepository
{
    public function listForEcole(int $ecoleId): Collection
    {
        return AnneeScolaire::query()->where('ecole_id', $ecoleId)->orderByDesc('date_debut')->get();
    }

    public function save(AnneeScolaire $annee): void
    {
        $annee->save();
    }

    public function findInEcoleOrFail(string $uuid, int $ecoleId): AnneeScolaire
    {
        return AnneeScolaire::query()->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function currentOfEcole(int $ecoleId): ?AnneeScolaire
    {
        return AnneeScolaire::query()->where('ecole_id', $ecoleId)->where('est_courante', true)->first();
    }

    public function ecoleHasCurrent(int $ecoleId): bool
    {
        return AnneeScolaire::query()->where('ecole_id', $ecoleId)->where('est_courante', true)->exists();
    }

    public function clearCurrent(int $ecoleId): void
    {
        AnneeScolaire::query()->where('ecole_id', $ecoleId)->where('est_courante', true)->update(['est_courante' => false]);
    }
}
