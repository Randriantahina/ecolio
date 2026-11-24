<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Scolarite\Domain\Contracts\StructureRepository;

/** The screen the directrice described: one row per pupil, one box per month (spec 0.1, M4). */
final class RegistreEcolageService
{
    public function __construct(
        private readonly EcheanceRepository $echeances,
        private readonly StructureRepository $structure,
    ) {}

    /**
     * @return list<array{eleve: array<string,mixed>, echeances: list<array<string,mixed>>}>
     */
    public function pour(Utilisateur $acteur, string $classeUuid, ?string $periode, ?bool $ponctuels = null): array
    {
        $ecoleId = (int) $acteur->ecole_id;
        $classe = $this->structure->classeOrFail($classeUuid, $ecoleId);
        $echeances = $this->echeances->forClassePeriode($ecoleId, $classe->id, $periode, $ponctuels);

        $ids = $echeances->pluck('id')->all();
        $payes = $this->echeances->paidAmounts($ids);
        $recus = $this->echeances->lastReceiptNumbers($ids);

        return $echeances->groupBy('eleve_id')->map(function ($groupe) use ($payes, $recus): array {
            $eleve = $groupe->first()->eleve;

            return [
                'eleve' => [
                    'uuid' => $eleve->uuid,
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenoms' => $eleve->prenoms,
                ],
                'echeances' => $groupe->map(function (Echeance $e) use ($payes, $recus): array {
                    $paye = min($payes[$e->id] ?? 0, $e->net()->amount());

                    return [
                        'uuid' => $e->uuid,
                        'libelle' => $e->libelle,
                        'periode' => $e->periode,
                        'type' => $e->typeFrais->nom,
                        'montant_ar' => $e->montant_ar,
                        'remise_ar' => $e->remise_ar,
                        'paye_ar' => $paye,
                        'solde_ar' => $e->net()->amount() - $paye,
                        'statut' => $e->statut->value,
                        'recu' => $recus[$e->id]['numero'] ?? null,
                        'paiement_uuid' => $recus[$e->id]['paiement_uuid'] ?? null,
                    ];
                })->values()->all(),
            ];
        })->values()->sortBy(fn (array $row) => $row['eleve']['matricule'])->values()->all();
    }
}
