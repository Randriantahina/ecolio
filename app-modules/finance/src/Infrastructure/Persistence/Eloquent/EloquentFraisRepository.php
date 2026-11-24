<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Finance\Domain\Contracts\FraisRepository;
use Modules\Finance\Domain\Models\Tarif;
use Modules\Finance\Domain\Models\TypeFrais;

final class EloquentFraisRepository implements FraisRepository
{
    public function types(int $ecoleId): Collection
    {
        return TypeFrais::query()->where('ecole_id', $ecoleId)->orderBy('nom')->get();
    }

    public function saveType(TypeFrais $type): void
    {
        $type->save();
    }

    public function typeOrFail(string $uuid, int $ecoleId): TypeFrais
    {
        return TypeFrais::query()->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function tarifsOfYear(int $ecoleId, int $anneeId): Collection
    {
        return Tarif::query()->with(['typeFrais', 'niveau', 'classe'])->where('ecole_id', $ecoleId)->where('annee_scolaire_id', $anneeId)->get();
    }

    public function saveTarif(Tarif $tarif): void
    {
        $tarif->save();
    }

    public function tarifOrFail(string $uuid, int $ecoleId): Tarif
    {
        return Tarif::query()->with('typeFrais')->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function deleteTarif(Tarif $tarif): void
    {
        $tarif->delete();
    }
}
