<?php

declare(strict_types=1);

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Models\Tarif;
use App\Domain\Finance\Models\TypeFrais;
use Illuminate\Support\Collection;

interface FraisRepository
{
    /** @return Collection<int, TypeFrais> */
    public function types(int $ecoleId): Collection;

    public function saveType(TypeFrais $type): void;

    public function typeOrFail(string $uuid, int $ecoleId): TypeFrais;

    /** @return Collection<int, Tarif> */
    public function tarifsOfYear(int $ecoleId, int $anneeId): Collection;

    public function saveTarif(Tarif $tarif): void;

    public function tarifOrFail(string $uuid, int $ecoleId): Tarif;

    public function deleteTarif(Tarif $tarif): void;
}
