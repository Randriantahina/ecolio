<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Domain\Enums\StatutPaiement;
use Modules\Finance\Domain\Models\Paiement;

/**
 * RG-R4: everything a receipt must show. `reste_a_payer_ar` is passed in by the controller because
 * it spans other instalments, which a single payment does not know about.
 *
 * @mixin Paiement
 */
final class PaiementResource extends JsonResource
{
    public function __construct($resource, private readonly ?int $resteAPayer = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $classe = $this->eleve->inscriptionActive?->classe;
        $ecole = $this->ecole;

        return [
            'uuid' => $this->uuid,
            'numero' => $this->recu?->numero_affiche,
            'statut' => $this->statut->value,
            'annule' => $this->statut === StatutPaiement::Annule,
            'motif_annulation' => $this->motif_annulation,
            'date_paiement' => $this->date_paiement->toDateString(),
            'mode' => $this->mode->value,
            'reference_externe' => $this->reference_externe,
            'total_ar' => $this->total_ar,
            'reste_a_payer_ar' => $this->resteAPayer,
            'eleve' => [
                'uuid' => $this->eleve->uuid,
                'matricule' => $this->eleve->matricule,
                'nom' => $this->eleve->nom,
                'prenoms' => $this->eleve->prenoms,
                'classe' => $classe?->nom,
            ],
            'ecole' => $ecole ? ['nom' => $ecole->nom, 'adresse' => $ecole->adresse, 'telephone' => $ecole->telephone] : null,
            'lignes' => $this->lignes->map(fn ($l) => [
                'libelle' => $l->echeance->libelle,
                'periode' => $l->echeance->periode,
                'montant_ar' => $l->montant_ar,
            ])->values()->all(),
            // RG-R6: a payment receipt, deliberately not an invoice.
            'mention' => 'Reçu de paiement',
        ];
    }
}
