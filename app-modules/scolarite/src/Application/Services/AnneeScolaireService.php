<?php

declare(strict_types=1);

namespace Modules\Scolarite\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Scolarite\Application\DTOs\CreateAnneeScolaireDto;
use Modules\Scolarite\Application\DTOs\UpdateAnneeScolaireDto;
use Modules\Scolarite\Domain\Contracts\AnneeScolaireRepository;
use Modules\Scolarite\Domain\Exceptions\InvalidAnneeScolaireException;
use Modules\Scolarite\Domain\Models\AnneeScolaire;

final class AnneeScolaireService
{
    public function __construct(private readonly AnneeScolaireRepository $annees) {}

    /** @return Collection<int, AnneeScolaire> */
    public function lister(Utilisateur $acteur): Collection
    {
        return $this->annees->listForEcole((int) $acteur->ecole_id);
    }

    public function creer(Utilisateur $acteur, CreateAnneeScolaireDto $dto): AnneeScolaire
    {
        return DB::transaction(function () use ($acteur, $dto): AnneeScolaire {
            $ecoleId = (int) $acteur->ecole_id;

            // A school with no current year would break every screen that defaults to it,
            // so the first year created takes the role without asking.
            $courante = $dto->estCourante || ! $this->annees->ecoleHasCurrent($ecoleId);
            if ($courante) {
                $this->annees->clearCurrent($ecoleId);
            }

            $annee = new AnneeScolaire([
                'libelle' => $dto->libelle,
                'date_debut' => $dto->dateDebut,
                'date_fin' => $dto->dateFin,
                'est_courante' => $courante,
            ]);
            // Always the actor's school, never a client-supplied one.
            $annee->ecole_id = $acteur->ecole_id;
            $this->annees->save($annee);

            return $annee;
        });
    }

    public function modifier(Utilisateur $acteur, string $uuid, UpdateAnneeScolaireDto $dto): AnneeScolaire
    {
        return DB::transaction(function () use ($acteur, $uuid, $dto): AnneeScolaire {
            $annee = $this->annees->findInEcoleOrFail($uuid, (int) $acteur->ecole_id);

            $debut = $dto->dateDebut ?? $annee->date_debut->toDateString();
            $fin = $dto->dateFin ?? $annee->date_fin->toDateString();
            // Checked on the merged values: a partial update can only be judged against what is stored.
            if ($fin <= $debut) {
                throw InvalidAnneeScolaireException::endsBeforeStart();
            }

            $annee->fill(array_filter([
                'libelle' => $dto->libelle,
                'date_debut' => $dto->dateDebut,
                'date_fin' => $dto->dateFin,
            ], fn ($valeur) => $valeur !== null));
            $this->annees->save($annee);

            return $annee;
        });
    }

    /** RG-S1: demote then promote inside one transaction, so there is never zero or two current years. */
    public function definirCourante(Utilisateur $acteur, string $uuid): AnneeScolaire
    {
        return DB::transaction(function () use ($acteur, $uuid): AnneeScolaire {
            $ecoleId = (int) $acteur->ecole_id;
            $annee = $this->annees->findInEcoleOrFail($uuid, $ecoleId);

            $this->annees->clearCurrent($ecoleId);
            $annee->est_courante = true;
            $this->annees->save($annee);

            return $annee;
        });
    }
}
