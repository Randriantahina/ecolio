<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spreadsheet shapes of the money screens (M10): the register view and the unpaid list. */
final class ExportFinanceService
{
    private const STATUTS = ['du' => 'À payer', 'partiel' => 'Partiel', 'paye' => 'Payé', 'annule' => 'Annulé'];

    public function __construct(private readonly EcheanceRepository $echeances) {}

    /**
     * @param  list<array<string, mixed>>  $lignes  the register rows as the page shows them
     * @return array{0: list<string>, 1: list<list<scalar|null>>}
     */
    public function registre(array $lignes): array
    {
        return [
            ['Matricule', 'Élève', 'Frais', 'Montant', 'Payé', 'Reste', 'Statut', 'Reçu'],
            collect($lignes)->map(fn (array $l): array => [
                $l['matricule'], $l['nom'], $l['libelle'], $l['montant_ar'], $l['paye_ar'], $l['solde_ar'],
                self::STATUTS[$l['statut']] ?? $l['statut'], $l['recu'],
            ])->values()->all(),
        ];
    }

    /**
     * Every instalment still owed in the school, whatever the month: the list the directeur chases.
     *
     * @return array{0: list<string>, 1: list<list<scalar|null>>}
     */
    public function impayes(Utilisateur $acteur): array
    {
        $echeances = $this->echeances->impayees((int) $acteur->ecole_id);
        $payes = $this->echeances->paidAmounts($echeances->pluck('id')->all());

        return [
            ['Matricule', 'Élève', 'Classe', 'Frais', 'Montant', 'Payé', 'Reste', 'Statut'],
            $echeances->map(function (Echeance $e) use ($payes): array {
                $net = $e->net()->amount();
                $paye = min($payes[$e->id] ?? 0, $net);

                return [
                    $e->eleve->matricule, trim($e->eleve->nom.' '.($e->eleve->prenoms ?? '')),
                    $e->eleve->inscriptionActive?->classe?->nom, $e->libelle, $net, $paye, $net - $paye,
                    self::STATUTS[$e->statut->value],
                ];
            })->values()->all(),
        ];
    }
}
