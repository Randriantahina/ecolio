<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Eleves\Domain\Models\Inscription;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Contracts\FraisRepository;
use Modules\Finance\Domain\Enums\Periodicite;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Finance\Domain\Models\Tarif;
use Modules\Scolarite\Domain\Models\AnneeScolaire;

/**
 * Turns the year's tariffs into instalments for one enrolment (RG-S2: instalments belong to a year).
 * Idempotent: an instalment that already exists is never recreated or repriced.
 */
final class EcheanceGenerator
{
    public function __construct(
        private readonly FraisRepository $frais,
        private readonly EcheanceRepository $echeances,
    ) {}

    public function pourInscription(Inscription $inscription): void
    {
        $inscription->loadMissing('classe', 'anneeScolaire');
        $classe = $inscription->classe;
        $annee = $inscription->anneeScolaire;

        $tarifs = $this->frais->tarifsOfYear((int) $inscription->ecole_id, $annee->id)
            ->filter(fn (Tarif $t) => $t->type_frais_id !== null && $t->typeFrais->actif)
            ->filter(fn (Tarif $t) => ($t->classe_id === null && $t->niveau_id === null)
                || $t->classe_id === $classe->id
                || ($t->classe_id === null && $t->niveau_id === $classe->niveau_id));

        // The most specific tariff wins per fee type: class over level over whole school.
        $parType = $tarifs->groupBy('type_frais_id')->map(fn (Collection $group) => $group->sortByDesc(
            fn (Tarif $t) => $t->classe_id !== null ? 2 : ($t->niveau_id !== null ? 1 : 0),
        )->first());

        foreach ($parType as $tarif) {
            foreach ($this->periodes($tarif->typeFrais->periodicite, $annee) as [$periode, $suffixe]) {
                if ($this->echeances->exists($inscription->id, $tarif->type_frais_id, $periode)) {
                    continue;
                }

                $echeance = new Echeance([
                    'periode' => $periode,
                    'libelle' => trim($tarif->typeFrais->nom.' '.$suffixe),
                    'montant_ar' => $tarif->montant_ar,
                ]);
                $echeance->ecole_id = $inscription->ecole_id;
                $echeance->inscription_id = $inscription->id;
                $echeance->eleve_id = $inscription->eleve_id;
                $echeance->type_frais_id = $tarif->type_frais_id;
                $this->echeances->save($echeance);
            }
        }
    }

    /** @return list<array{0:?string,1:string}> [periode, label suffix] */
    private function periodes(Periodicite $periodicite, AnneeScolaire $annee): array
    {
        $debut = CarbonImmutable::parse($annee->date_debut)->startOfMonth();
        $fin = CarbonImmutable::parse($annee->date_fin)->startOfMonth();

        $mois = [];
        for ($m = $debut; $m <= $fin; $m = $m->addMonth()) {
            $mois[] = $m;
        }

        return match ($periodicite) {
            Periodicite::Unique, Periodicite::Annuel => [[null, '']],
            Periodicite::Mensuel => array_map(
                fn (CarbonImmutable $m) => [$m->format('Y-m'), mb_convert_case($m->locale('fr')->translatedFormat('F Y'), MB_CASE_TITLE)],
                $mois,
            ),
            Periodicite::Trimestriel => $this->trimestres($mois, $debut),
        };
    }

    /** @param  list<CarbonImmutable>  $mois */
    private function trimestres(array $mois, CarbonImmutable $debut): array
    {
        $taille = max(1, (int) ceil(count($mois) / 3));
        $resultat = [];
        foreach (array_chunk($mois, $taille) as $i => $_) {
            $resultat[] = [$debut->format('Y').'-T'.($i + 1), 'Trimestre '.($i + 1)];
        }

        return $resultat;
    }
}
