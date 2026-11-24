<?php

declare(strict_types=1);

namespace Modules\Eleves\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Identity\Domain\Models\Utilisateur;

final class DepartService
{
    public function __construct(
        private readonly EleveRepository $eleves,
        private readonly EcheanceRepository $echeances,
    ) {}

    /**
     * RG-E2 / RG-P6: the pupil is kept (never deleted) and so are the receipts; only instalments
     * for months AFTER the departure and not touched by any payment stop being due. Money already
     * owed stays owed.
     */
    public function enregistrer(Utilisateur $acteur, string $uuid, StatutEleve $statut, string $dateSortie, ?string $motif, ?string $commentaire): Eleve
    {
        return DB::transaction(function () use ($acteur, $uuid, $statut, $dateSortie, $motif, $commentaire): Eleve {
            $eleve = $this->eleves->findOrFail($uuid, (int) $acteur->ecole_id);

            if ($statut === StatutEleve::Actif) {
                throw ValidationException::withMessages(['statut' => 'Un départ ne peut pas laisser l\'élève actif.']);
            }
            if ($eleve->statut !== StatutEleve::Actif) {
                throw ValidationException::withMessages(['statut' => 'Cet élève a déjà quitté l\'école.']);
            }

            $eleve->statut = $statut;
            $eleve->date_sortie = $dateSortie;
            $eleve->motif_sortie = $motif;
            $eleve->commentaire_sortie = $commentaire;
            $this->eleves->save($eleve);

            $this->echeances->cancelFutureUnpaid($eleve->id, substr($dateSortie, 0, 7));

            return $this->eleves->findOrFail($uuid, (int) $acteur->ecole_id);
        });
    }
}
