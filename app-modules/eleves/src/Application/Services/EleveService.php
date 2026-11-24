<?php

declare(strict_types=1);

namespace Modules\Eleves\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\DTOs\UpdateEleveDto;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Eleves\Domain\Exceptions\DuplicateEleveException;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Models\Utilisateur;

final class EleveService
{
    public function __construct(
        private readonly EleveRepository $eleves,
        private readonly MatriculeGenerator $matricules,
        private readonly InscriptionService $inscriptions,
        private readonly ChampService $champs,
    ) {}

    /**
     * @param  array{q?:?string,classe?:?string,statut?:?string}  $filtres
     * @return CursorPaginator<Eleve>
     */
    public function lister(Utilisateur $acteur, array $filtres, int $perPage = 50): CursorPaginator
    {
        return $this->eleves->paginate((int) $acteur->ecole_id, $filtres, $perPage);
    }

    /** @return array{actifs:int, sans_classe:int} */
    public function effectifs(Utilisateur $acteur): array
    {
        return $this->eleves->effectifs((int) $acteur->ecole_id);
    }

    public function voir(Utilisateur $acteur, string $uuid): Eleve
    {
        return $this->eleves->findOrFail($uuid, (int) $acteur->ecole_id);
    }

    /** @return array{eleve: Eleve, effectif_depasse: bool} */
    public function creer(Utilisateur $acteur, CreateEleveDto $dto): array
    {
        return DB::transaction(function () use ($acteur, $dto): array {
            $ecoleId = (int) $acteur->ecole_id;

            if (! $dto->confirmerDoublon
                && ($doublon = $this->eleves->findDuplicate($ecoleId, $dto->nom, $dto->prenoms, $dto->dateNaissance)) !== null) {
                throw new DuplicateEleveException($doublon);
            }

            if ($dto->matricule !== null && $this->eleves->matriculeExists($ecoleId, $dto->matricule)) {
                throw ValidationException::withMessages(['matricule' => 'Ce matricule existe déjà dans l\'école.']);
            }

            $champsPerso = $this->champs->valider($acteur, EntiteChamp::Eleve, $dto->champsPerso);

            $eleve = new Eleve([
                'matricule' => $dto->matricule ?? $this->matricules->next($ecoleId),
                'nom' => $dto->nom,
                'prenoms' => $dto->prenoms,
                'sexe' => $dto->sexe,
                'date_naissance' => $dto->dateNaissance,
                'lieu_naissance' => $dto->lieuNaissance,
                'date_entree' => now()->toDateString(),
                'champs_perso' => $champsPerso,
            ]);
            $eleve->ecole_id = $acteur->ecole_id;
            $this->eleves->save($eleve);

            $depasse = false;
            if ($dto->classeUuid !== null) {
                $depasse = $this->inscriptions->inscrire($acteur, $eleve, $dto->classeUuid, $dto->typeInscription, $dto->estRedoublant)['effectif_depasse'];
            }

            return ['eleve' => $this->eleves->findOrFail($eleve->uuid, $ecoleId), 'effectif_depasse' => $depasse];
        });
    }

    public function modifier(Utilisateur $acteur, string $uuid, UpdateEleveDto $dto): Eleve
    {
        return DB::transaction(function () use ($acteur, $uuid, $dto): Eleve {
            $ecoleId = (int) $acteur->ecole_id;
            $eleve = $this->eleves->findOrFail($uuid, $ecoleId);

            if ($dto->matricule !== null && $dto->matricule !== $eleve->matricule) {
                // RG-E1: immutable, correction reserved to the directeur (journalised by activitylog).
                if (! $acteur->can('corrigerMatricule', Eleve::class)) {
                    throw new AuthorizationException('Seul le directeur peut corriger un matricule.');
                }
                if ($this->eleves->matriculeExists($ecoleId, $dto->matricule, $eleve->id)) {
                    throw ValidationException::withMessages(['matricule' => 'Ce matricule existe déjà dans l\'école.']);
                }
                $eleve->matricule = $dto->matricule;
            }

            $eleve->fill(array_filter([
                'nom' => $dto->nom,
                'prenoms' => $dto->prenoms,
                'sexe' => $dto->sexe,
                'date_naissance' => $dto->dateNaissance,
                'lieu_naissance' => $dto->lieuNaissance,
            ], fn ($v) => $v !== null));
            if ($dto->champsPerso !== null) {
                // Values of deactivated fields stay stored (data kept); active ones follow the form, so a cleared field disappears.
                $actives = $this->champs->lister($acteur, EntiteChamp::Eleve)->pluck('cle')->all();
                $conserves = array_diff_key($eleve->champs_perso ?? [], array_flip($actives));
                $eleve->champs_perso = $conserves + $this->champs->valider($acteur, EntiteChamp::Eleve, $dto->champsPerso);
            }
            $this->eleves->save($eleve);

            return $eleve;
        });
    }
}
