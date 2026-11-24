<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;

interface ChampRepository
{
    /** @return Collection<int, ChampPersonnalise> ordered by `ordre` then id. */
    public function lister(int $ecoleId, EntiteChamp $entite, bool $actifsSeulement): Collection;

    /** Throws ModelNotFoundException for another school's field. */
    public function findOrFail(string $uuid, int $ecoleId): ChampPersonnalise;

    public function cleExiste(int $ecoleId, EntiteChamp $entite, string $cle): bool;

    public function compterActifs(int $ecoleId, EntiteChamp $entite): int;

    public function prochainOrdre(int $ecoleId, EntiteChamp $entite): int;

    public function save(ChampPersonnalise $champ): void;
}
