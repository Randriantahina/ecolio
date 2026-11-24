<?php

declare(strict_types=1);

namespace Modules\Dynamique\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Dynamique\Domain\Contracts\ChampRepository;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;

final class EloquentChampRepository implements ChampRepository
{
    public function lister(int $ecoleId, EntiteChamp $entite, bool $actifsSeulement): Collection
    {
        return ChampPersonnalise::query()->where('ecole_id', $ecoleId)->where('entite', $entite->value)
            ->when($actifsSeulement, fn ($q) => $q->where('actif', true))
            ->orderBy('ordre')->orderBy('id')->get();
    }

    public function findOrFail(string $uuid, int $ecoleId): ChampPersonnalise
    {
        return ChampPersonnalise::query()->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function cleExiste(int $ecoleId, EntiteChamp $entite, string $cle): bool
    {
        return ChampPersonnalise::query()->where('ecole_id', $ecoleId)->where('entite', $entite->value)->where('cle', $cle)->exists();
    }

    public function compterActifs(int $ecoleId, EntiteChamp $entite): int
    {
        return ChampPersonnalise::query()->where('ecole_id', $ecoleId)->where('entite', $entite->value)->where('actif', true)->count();
    }

    public function prochainOrdre(int $ecoleId, EntiteChamp $entite): int
    {
        return (int) ChampPersonnalise::query()->where('ecole_id', $ecoleId)->where('entite', $entite->value)->max('ordre') + 1;
    }

    public function save(ChampPersonnalise $champ): void
    {
        $champ->save();
    }
}
