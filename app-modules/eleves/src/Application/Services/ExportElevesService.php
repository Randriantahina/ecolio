<?php

declare(strict_types=1);

namespace Modules\Eleves\Application\Services;

use Illuminate\Support\Collection;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Models\Utilisateur;

/** Shapes pupils into spreadsheet rows (US3.5): core columns, then the school's custom fields (RG-D5). */
final class ExportElevesService
{
    public function __construct(private readonly ChampService $champs) {}

    /**
     * @param  Collection<int, Eleve>  $eleves  already scoped and filtered by the caller (the current view)
     * @return array{0: list<string>, 1: list<list<scalar|null>>}
     */
    public function tableau(Utilisateur $acteur, Collection $eleves): array
    {
        $champs = $this->champs->lister($acteur, EntiteChamp::Eleve);
        $eleves->loadMissing('inscriptionActive.classe');

        $entetes = ['Matricule', 'Nom', 'Prénoms', 'Sexe', 'Date de naissance', 'Classe', 'Statut', ...$champs->pluck('libelle')->all()];

        $lignes = $eleves->map(fn (Eleve $e): array => [
            $e->matricule, $e->nom, $e->prenoms, $e->sexe, $e->date_naissance?->format('d/m/Y'),
            $e->inscriptionActive?->classe?->nom, $e->statut->libelle(),
            ...$champs->map(function ($c) use ($e) {
                $valeur = $e->champs_perso[$c->cle] ?? null;

                return $c->type === TypeChamp::Booleen && $valeur !== null ? ($valeur ? 'Oui' : 'Non') : $valeur;
            })->all(),
        ])->values()->all();

        return [$entetes, $lignes];
    }
}
