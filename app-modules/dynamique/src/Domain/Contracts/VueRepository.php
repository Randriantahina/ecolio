<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Domain\Models\VueTableau;

interface VueRepository
{
    /** @return Collection<int, VueTableau> the user's own views and the school's shared ones, by name */
    public function visibles(int $ecoleId, int $utilisateurId, EntiteVue $entite): Collection;

    /** Throws ModelNotFoundException unless the view is the user's own or shared in the school. */
    public function visibleOrFail(string $uuid, int $ecoleId, int $utilisateurId): VueTableau;

    public function findOwn(int $ecoleId, int $utilisateurId, EntiteVue $entite, string $nom): ?VueTableau;

    public function defautDe(int $ecoleId, int $utilisateurId, EntiteVue $entite): ?VueTableau;

    public function clearDefaults(int $ecoleId, int $utilisateurId, EntiteVue $entite): void;

    public function save(VueTableau $vue): void;

    public function delete(VueTableau $vue): void;
}
