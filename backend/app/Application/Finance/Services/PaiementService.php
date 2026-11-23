<?php

declare(strict_types=1);

namespace App\Application\Finance\Services;

use App\Application\Finance\DTOs\EncaisserDto;
use App\Domain\Eleves\Contracts\EleveRepository;
use App\Domain\Finance\Contracts\CompteurRepository;
use App\Domain\Finance\Contracts\EcheanceRepository;
use App\Domain\Finance\Contracts\PaiementRepository;
use App\Domain\Finance\Enums\StatutEcheance;
use App\Domain\Finance\Enums\StatutPaiement;
use App\Domain\Finance\Models\Echeance;
use App\Domain\Finance\Models\Paiement;
use App\Domain\Finance\Models\Recu;
use App\Domain\Finance\ValueObjects\Money;
use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaiementService
{
    public function __construct(
        private readonly PaiementRepository $paiements,
        private readonly EcheanceRepository $echeances,
        private readonly EleveRepository $eleves,
        private readonly CompteurRepository $compteurs,
    ) {}

    /**
     * Checking a box IS paying (RG-P1): there is no "paid" state without a receipt, so the payment,
     * its lines, the receipt number and the new instalment statuses are one transaction.
     *
     * @return array{paiement: Paiement, rejoue: bool}
     */
    public function encaisser(Utilisateur $acteur, EncaisserDto $dto): array
    {
        $ecoleId = (int) $acteur->ecole_id;

        return DB::transaction(function () use ($acteur, $dto, $ecoleId): array {
            // Offline replay: the same client_uuid answers with the original payment instead of paying twice.
            if ($dto->clientUuid !== null && ($existant = $this->paiements->findByClientUuid($dto->clientUuid, $ecoleId)) !== null) {
                return ['paiement' => $existant, 'rejoue' => true];
            }

            $eleve = $this->eleves->findOrFail($dto->eleveUuid, $ecoleId);

            $uuids = array_column($dto->lignes, 'echeance_uuid');
            if (count($uuids) !== count(array_unique($uuids))) {
                throw ValidationException::withMessages(['lignes' => 'Une échéance ne peut apparaître qu\'une fois dans un paiement.']);
            }

            $echeances = $this->echeances->lockByUuids($uuids, $ecoleId)->keyBy('uuid');
            $lignes = [];
            $montants = [];

            foreach ($dto->lignes as $i => $ligne) {
                /** @var Echeance|null $echeance */
                $echeance = $echeances->get($ligne['echeance_uuid']);
                if ($echeance === null || $echeance->eleve_id !== $eleve->id) {
                    throw ValidationException::withMessages(["lignes.$i.echeance_uuid" => 'Échéance introuvable pour cet élève.']);
                }
                if ($echeance->statut === StatutEcheance::Annule) {
                    throw ValidationException::withMessages(["lignes.$i.echeance_uuid" => 'Cette échéance est annulée.']);
                }

                $solde = $echeance->net()->minus(Money::ariary(min($this->echeances->paidAmount($echeance->id), $echeance->net()->amount())));
                $montant = Money::ariary($ligne['montant_ar']);
                // A line may never exceed what is still owed (RG-P2 keeps partial payments possible).
                if ($montant->isGreaterThan($solde)) {
                    throw ValidationException::withMessages(["lignes.$i.montant_ar" => 'Le montant dépasse le reste à payer ('.$solde->amount().' Ar).']);
                }

                $lignes[] = ['echeance_id' => $echeance->id, 'montant_ar' => $montant->amount()];
                $montants[] = $montant;
            }

            $paiement = new Paiement([
                'eleve_id' => $eleve->id,
                'date_paiement' => $dto->datePaiement,
                'total_ar' => Money::sum($montants)->amount(),
                'mode' => $dto->mode,
                'reference_externe' => $dto->referenceExterne,
                'client_uuid' => $dto->clientUuid,
                'created_by' => $acteur->id,
            ]);
            $paiement->ecole_id = $acteur->ecole_id;
            $this->paiements->save($paiement);
            $this->paiements->saveLignes($paiement, $lignes);

            $this->emettreRecu($paiement);
            foreach ($echeances as $echeance) {
                $this->recalculer($echeance);
            }

            return ['paiement' => $this->paiements->findOrFail($paiement->uuid, $ecoleId), 'rejoue' => false];
        });
    }

    /** RG-P4/P7: unchecking is a void with a mandatory reason; the receipt keeps its number. */
    public function annuler(Utilisateur $acteur, string $uuid, string $motif): Paiement
    {
        $ecoleId = (int) $acteur->ecole_id;

        return DB::transaction(function () use ($acteur, $uuid, $motif, $ecoleId): Paiement {
            $paiement = $this->paiements->findForUpdateOrFail($uuid, $ecoleId);

            if ($paiement->statut === StatutPaiement::Annule) {
                throw ValidationException::withMessages(['paiement' => 'Ce paiement est déjà annulé.']);
            }

            $paiement->statut = StatutPaiement::Annule;
            $paiement->annule_le = now();
            $paiement->annule_par = $acteur->id;
            $paiement->motif_annulation = $motif;
            $this->paiements->save($paiement);

            $this->echeances->lockByUuids(
                $paiement->lignes()->with('echeance')->get()->pluck('echeance.uuid')->all(),
                $ecoleId,
            )->each(fn (Echeance $e) => $this->recalculer($e));

            return $this->paiements->findOrFail($uuid, $ecoleId);
        });
    }

    public function voir(Utilisateur $acteur, string $uuid): Paiement
    {
        return $this->paiements->findOrFail($uuid, (int) $acteur->ecole_id);
    }

    /** Remaining balance of a pupil across all live instalments, printed on the receipt (RG-R4). */
    public function resteAPayer(int $eleveId): int
    {
        return $this->echeances->forEleve($eleveId)
            ->filter(fn (Echeance $e) => $e->statut !== StatutEcheance::Annule)
            ->sum(fn (Echeance $e) => max(0, $e->net()->amount() - $this->echeances->paidAmount($e->id)));
    }

    /** RG-R1: the number is taken inside the payment transaction, from the locked counter. */
    private function emettreRecu(Paiement $paiement): void
    {
        $annee = (int) $paiement->date_paiement->format('Y');
        $numero = $this->compteurs->nextValue((int) $paiement->ecole_id, "recu:{$annee}");

        $recu = new Recu([
            'paiement_id' => $paiement->id,
            'annee' => $annee,
            'numero' => $numero,
            'numero_affiche' => sprintf('%d-%04d', $annee, $numero),
            'emis_le' => now(),
        ]);
        $recu->ecole_id = $paiement->ecole_id;
        $this->paiements->saveRecu($recu);
    }

    /** Status is derived from the lines of valid payments, never typed in (spec M4). */
    private function recalculer(Echeance $echeance): void
    {
        if ($echeance->statut === StatutEcheance::Annule) {
            return;
        }

        $paye = $this->echeances->paidAmount($echeance->id);
        $echeance->statut = match (true) {
            $paye <= 0 => StatutEcheance::Du,
            $paye < $echeance->net()->amount() => StatutEcheance::Partiel,
            default => StatutEcheance::Paye,
        };
        $this->echeances->save($echeance);
    }
}
