<?php

declare(strict_types=1);

namespace Modules\Scolarite\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Scolarite\Domain\Contracts\StructureRepository;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;

final class EloquentStructureRepository implements StructureRepository
{
    public function niveaux(int $ecoleId): Collection
    {
        return Niveau::query()->where('ecole_id', $ecoleId)->orderBy('ordre')->orderBy('nom')->get();
    }

    public function saveNiveau(Niveau $niveau): void
    {
        $niveau->save();
    }

    public function niveauOrFail(string $uuid, int $ecoleId): Niveau
    {
        return Niveau::query()->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function classesOfYear(int $ecoleId, int $anneeId): Collection
    {
        return Classe::query()->with('niveau')
            ->where('ecole_id', $ecoleId)->where('annee_scolaire_id', $anneeId)
            ->orderBy('nom')->get();
    }

    public function saveClasse(Classe $classe): void
    {
        $classe->save();
    }

    public function classeOrFail(string $uuid, int $ecoleId): Classe
    {
        return Classe::query()->with('niveau')->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function deleteClasse(Classe $classe): void
    {
        $classe->delete();
    }
}
