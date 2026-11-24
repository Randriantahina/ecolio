<?php

declare(strict_types=1);

namespace App\Application\Scolarite\Services;

use App\Domain\Eleves\Contracts\InscriptionRepository;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Scolarite\Contracts\AnneeScolaireRepository;
use App\Domain\Scolarite\Contracts\StructureRepository;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class StructureService
{
    public function __construct(
        private readonly StructureRepository $structure,
        private readonly AnneeScolaireRepository $annees,
        private readonly InscriptionRepository $inscriptions,
    ) {}

    /** @return Collection<int, Niveau> */
    public function niveaux(Utilisateur $acteur): Collection
    {
        return $this->structure->niveaux((int) $acteur->ecole_id);
    }

    /** @param array{nom:string,cycle?:string,ordre?:int} $data */
    public function creerNiveau(Utilisateur $acteur, array $data): Niveau
    {
        $niveau = new Niveau($data);
        $niveau->ecole_id = $acteur->ecole_id;
        $this->structure->saveNiveau($niveau);

        return $niveau;
    }

    /** @param array<string,mixed> $data */
    public function modifierNiveau(Utilisateur $acteur, string $uuid, array $data): Niveau
    {
        $niveau = $this->structure->niveauOrFail($uuid, (int) $acteur->ecole_id);
        $niveau->fill($data);
        $this->structure->saveNiveau($niveau);

        return $niveau;
    }

    /** Defaults to the current year so the common screen needs no parameter (RG-S2: classes belong to a year). @return Collection<int, Classe> */
    public function classes(Utilisateur $acteur, ?string $anneeUuid): Collection
    {
        $ecoleId = (int) $acteur->ecole_id;
        $annee = $anneeUuid !== null
            ? $this->annees->findInEcoleOrFail($anneeUuid, $ecoleId)
            : $this->annees->currentOfEcole($ecoleId);

        return $annee === null ? new Collection : $this->structure->classesOfYear($ecoleId, $annee->id);
    }

    /** @param array{nom:string,niveau_uuid:string,annee_scolaire_uuid?:?string,effectif_max?:?int} $data */
    public function creerClasse(Utilisateur $acteur, array $data): Classe
    {
        $ecoleId = (int) $acteur->ecole_id;
        $niveau = $this->structure->niveauOrFail($data['niveau_uuid'], $ecoleId);
        $annee = ($data['annee_scolaire_uuid'] ?? null) !== null
            ? $this->annees->findInEcoleOrFail($data['annee_scolaire_uuid'], $ecoleId)
            : $this->annees->currentOfEcole($ecoleId);

        abort_if($annee === null, 422, 'Aucune année scolaire courante : créez-en une d\'abord.');
        $this->assurerNomLibre($ecoleId, $annee->id, $data['nom']);

        $classe = new Classe(['nom' => $data['nom'], 'effectif_max' => $data['effectif_max'] ?? null]);
        $classe->ecole_id = $acteur->ecole_id;
        $classe->annee_scolaire_id = $annee->id;
        $classe->niveau_id = $niveau->id;
        $this->structure->saveClasse($classe);

        return $classe->load('niveau');
    }

    /** Spec UQ(ecole, année, nom): reported as a field error rather than a raw SQL failure. */
    private function assurerNomLibre(int $ecoleId, int $anneeId, string $nom, ?int $sauf = null): void
    {
        $existe = $this->structure->classesOfYear($ecoleId, $anneeId)
            ->contains(fn (Classe $c) => $c->nom === $nom && $c->id !== $sauf);

        if ($existe) {
            throw ValidationException::withMessages(['nom' => 'Une classe porte déjà ce nom cette année.']);
        }
    }

    /** @param array<string,mixed> $data */
    public function modifierClasse(Utilisateur $acteur, string $uuid, array $data): Classe
    {
        $classe = $this->structure->classeOrFail($uuid, (int) $acteur->ecole_id);
        if (isset($data['nom'])) {
            $this->assurerNomLibre((int) $acteur->ecole_id, $classe->annee_scolaire_id, $data['nom'], $classe->id);
        }
        $classe->fill($data);
        $this->structure->saveClasse($classe);

        return $classe;
    }

    public function supprimerClasse(Utilisateur $acteur, string $uuid): void
    {
        $classe = $this->structure->classeOrFail($uuid, (int) $acteur->ecole_id);

        // Deleting a class that still holds pupils would leave them enrolled in nothing.
        if ($this->inscriptions->countActiveInClasse($classe->id) > 0) {
            throw ValidationException::withMessages(['classe' => 'Cette classe contient encore des élèves : déplacez-les avant de la supprimer.']);
        }

        $this->structure->deleteClasse($classe);
    }
}
