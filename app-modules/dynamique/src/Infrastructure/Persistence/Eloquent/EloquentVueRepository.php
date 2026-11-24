<?php

declare(strict_types=1);

namespace Modules\Dynamique\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Dynamique\Domain\Contracts\VueRepository;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Domain\Models\VueTableau;

final class EloquentVueRepository implements VueRepository
{
    public function visibles(int $ecoleId, int $utilisateurId, EntiteVue $entite): Collection
    {
        return $this->query($ecoleId, $utilisateurId)->where('entite', $entite->value)->orderBy('nom')->get();
    }

    public function visibleOrFail(string $uuid, int $ecoleId, int $utilisateurId): VueTableau
    {
        return $this->query($ecoleId, $utilisateurId)->where('uuid', $uuid)->firstOrFail();
    }

    public function findOwn(int $ecoleId, int $utilisateurId, EntiteVue $entite, string $nom): ?VueTableau
    {
        return VueTableau::query()->where('ecole_id', $ecoleId)->where('utilisateur_id', $utilisateurId)
            ->where('entite', $entite->value)->where('nom', $nom)->first();
    }

    public function defautDe(int $ecoleId, int $utilisateurId, EntiteVue $entite): ?VueTableau
    {
        return VueTableau::query()->where('ecole_id', $ecoleId)->where('utilisateur_id', $utilisateurId)
            ->where('entite', $entite->value)->where('est_par_defaut', true)->first();
    }

    public function clearDefaults(int $ecoleId, int $utilisateurId, EntiteVue $entite): void
    {
        VueTableau::query()->where('ecole_id', $ecoleId)->where('utilisateur_id', $utilisateurId)
            ->where('entite', $entite->value)->update(['est_par_defaut' => false]);
    }

    public function save(VueTableau $vue): void
    {
        $vue->save();
    }

    public function delete(VueTableau $vue): void
    {
        $vue->delete();
    }

    /** @return Builder<VueTableau> */
    private function query(int $ecoleId, int $utilisateurId): Builder
    {
        return VueTableau::query()->where('ecole_id', $ecoleId)
            ->where(fn (Builder $q) => $q->where('utilisateur_id', $utilisateurId)->orWhere('est_partagee', true));
    }
}
