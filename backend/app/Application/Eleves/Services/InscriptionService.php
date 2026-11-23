<?php

declare(strict_types=1);

namespace App\Application\Eleves\Services;

use App\Domain\Eleves\Contracts\InscriptionRepository;
use App\Domain\Eleves\Models\Eleve;
use App\Domain\Eleves\Models\Inscription;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Scolarite\Contracts\StructureRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InscriptionService
{
    public function __construct(
        private readonly InscriptionRepository $inscriptions,
        private readonly StructureRepository $structure,
    ) {}

    /** @return array{inscription: Inscription, effectif_depasse: bool} */
    public function inscrire(Utilisateur $acteur, Eleve $eleve, string $classeUuid, string $type = 'nouvelle', bool $redoublant = false): array
    {
        return DB::transaction(function () use ($acteur, $eleve, $classeUuid, $type, $redoublant): array {
            $classe = $this->structure->classeOrFail($classeUuid, (int) $acteur->ecole_id);

            // RG-I1: one active enrolment per pupil and year.
            if ($this->inscriptions->activeFor($eleve->id, $classe->annee_scolaire_id) !== null) {
                throw ValidationException::withMessages(['classe_uuid' => 'Cet élève est déjà inscrit cette année.']);
            }

            $inscription = new Inscription(['date_inscription' => now()->toDateString(), 'type' => $type, 'est_redoublant' => $redoublant]);
            $inscription->ecole_id = $acteur->ecole_id;
            $inscription->eleve_id = $eleve->id;
            $inscription->annee_scolaire_id = $classe->annee_scolaire_id;
            $inscription->classe_id = $classe->id;
            $this->inscriptions->save($inscription);

            // RG-I2: over capacity is a warning for the caller to show, never a refusal.
            $depasse = $classe->effectif_max !== null
                && $this->inscriptions->countActiveInClasse($classe->id) > $classe->effectif_max;

            return ['inscription' => $inscription, 'effectif_depasse' => $depasse];
        });
    }

    /** @return array{inscription: Inscription, effectif_depasse: bool} */
    public function changerClasse(Utilisateur $acteur, Eleve $eleve, string $classeUuid): array
    {
        return DB::transaction(function () use ($acteur, $eleve, $classeUuid): array {
            $classe = $this->structure->classeOrFail($classeUuid, (int) $acteur->ecole_id);
            $inscription = $this->inscriptions->activeFor($eleve->id, $classe->annee_scolaire_id);

            if ($inscription === null) {
                throw ValidationException::withMessages(['classe_uuid' => 'Aucune inscription active pour cette année : inscrivez l\'élève d\'abord.']);
            }

            if ($inscription->classe_id !== $classe->id) {
                $this->inscriptions->recordClassChange((int) $acteur->ecole_id, $inscription->id, $inscription->classe_id, $classe->id, $acteur->id);
                $inscription->classe_id = $classe->id;
                $this->inscriptions->save($inscription);
            }

            $depasse = $classe->effectif_max !== null
                && $this->inscriptions->countActiveInClasse($classe->id) > $classe->effectif_max;

            return ['inscription' => $inscription, 'effectif_depasse' => $depasse];
        });
    }
}
