<?php

declare(strict_types=1);

namespace Modules\Dynamique\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Domain\Contracts\VueRepository;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Domain\Models\VueTableau;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Saved table views (US3.4). Only the directeur shares them: the secretaire has personal views (spec section 1). */
final class VueService
{
    public function __construct(private readonly VueRepository $vues) {}

    /** @return Collection<int, VueTableau> */
    public function lister(Utilisateur $acteur, EntiteVue $entite): Collection
    {
        return $this->vues->visibles((int) $acteur->ecole_id, $acteur->id, $entite);
    }

    public function charger(Utilisateur $acteur, string $uuid): VueTableau
    {
        return $this->vues->visibleOrFail($uuid, (int) $acteur->ecole_id, $acteur->id);
    }

    public function parDefaut(Utilisateur $acteur, EntiteVue $entite): ?VueTableau
    {
        return $this->vues->defautDe((int) $acteur->ecole_id, $acteur->id, $entite);
    }

    /** @param  array<string, mixed>  $etat */
    public function enregistrer(Utilisateur $acteur, EntiteVue $entite, string $nom, array $etat, bool $partagee = false, bool $parDefaut = false): VueTableau
    {
        if ($partagee && ! $acteur->aRole(Role::Directeur)) {
            throw ValidationException::withMessages(['est_partagee' => 'Seul le directeur peut partager une vue avec l\'école.']);
        }

        return DB::transaction(function () use ($acteur, $entite, $nom, $etat, $partagee, $parDefaut): VueTableau {
            $ecoleId = (int) $acteur->ecole_id;
            $vue = $this->vues->findOwn($ecoleId, $acteur->id, $entite, $nom) ?? new VueTableau;

            if ($parDefaut) {
                $this->vues->clearDefaults($ecoleId, $acteur->id, $entite);
            }
            $vue->fill(['nom' => $nom, 'etat' => $etat, 'est_partagee' => $partagee, 'est_par_defaut' => $parDefaut]);
            $vue->ecole_id = $ecoleId;
            $vue->utilisateur_id = $acteur->id;
            $vue->entite = $entite;
            $this->vues->save($vue);

            return $vue;
        });
    }

    public function supprimer(Utilisateur $acteur, string $uuid): void
    {
        $vue = $this->vues->visibleOrFail($uuid, (int) $acteur->ecole_id, $acteur->id);
        if ($vue->utilisateur_id !== $acteur->id && ! $acteur->aRole(Role::Directeur)) {
            throw new AuthorizationException('Cette vue appartient à un collègue.');
        }
        $this->vues->delete($vue);
    }
}
