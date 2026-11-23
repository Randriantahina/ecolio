<?php

declare(strict_types=1);

namespace App\Application\Finance\Services;

use App\Domain\Eleves\Models\Inscription;
use App\Domain\Finance\Contracts\FraisRepository;
use App\Domain\Finance\Models\Tarif;
use App\Domain\Finance\Models\TypeFrais;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Scolarite\Contracts\AnneeScolaireRepository;
use App\Domain\Scolarite\Contracts\StructureRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FraisService
{
    public function __construct(
        private readonly FraisRepository $frais,
        private readonly AnneeScolaireRepository $annees,
        private readonly StructureRepository $structure,
        private readonly EcheanceGenerator $generator,
    ) {}

    /** @return Collection<int, TypeFrais> */
    public function types(Utilisateur $acteur): Collection
    {
        return $this->frais->types((int) $acteur->ecole_id);
    }

    /** @param array<string,mixed> $data */
    public function creerType(Utilisateur $acteur, array $data): TypeFrais
    {
        $type = new TypeFrais($data);
        $type->ecole_id = $acteur->ecole_id;
        $this->frais->saveType($type);

        return $type;
    }

    /** @param array<string,mixed> $data */
    public function modifierType(Utilisateur $acteur, string $uuid, array $data): TypeFrais
    {
        $type = $this->frais->typeOrFail($uuid, (int) $acteur->ecole_id);
        $type->fill($data);
        $this->frais->saveType($type);

        return $type;
    }

    /** @return Collection<int, Tarif> */
    public function tarifs(Utilisateur $acteur, ?string $anneeUuid): Collection
    {
        $annee = $this->annee($acteur, $anneeUuid);

        return $this->frais->tarifsOfYear((int) $acteur->ecole_id, $annee->id);
    }

    /** @param array{type_frais_uuid:string,montant_ar:int,niveau_uuid?:?string,classe_uuid?:?string,annee_scolaire_uuid?:?string} $data */
    public function creerTarif(Utilisateur $acteur, array $data): Tarif
    {
        return DB::transaction(function () use ($acteur, $data): Tarif {
            $ecoleId = (int) $acteur->ecole_id;
            $annee = $this->annee($acteur, $data['annee_scolaire_uuid'] ?? null);
            $type = $this->frais->typeOrFail($data['type_frais_uuid'], $ecoleId);

            if (! empty($data['niveau_uuid']) && ! empty($data['classe_uuid'])) {
                throw ValidationException::withMessages(['classe_uuid' => 'Un tarif vise un niveau ou une classe, pas les deux.']);
            }

            $tarif = new Tarif(['montant_ar' => $data['montant_ar']]);
            $tarif->ecole_id = $acteur->ecole_id;
            $tarif->annee_scolaire_id = $annee->id;
            $tarif->type_frais_id = $type->id;
            $tarif->niveau_id = ! empty($data['niveau_uuid']) ? $this->structure->niveauOrFail($data['niveau_uuid'], $ecoleId)->id : null;
            $tarif->classe_id = ! empty($data['classe_uuid']) ? $this->structure->classeOrFail($data['classe_uuid'], $ecoleId)->id : null;
            $this->frais->saveTarif($tarif);

            // Pupils enrolled before the tariff existed still get their instalments.
            Inscription::query()->where('ecole_id', $ecoleId)->where('annee_scolaire_id', $annee->id)->where('statut', 'active')
                ->get()->each(fn (Inscription $i) => $this->generator->pourInscription($i));

            return $tarif->load('typeFrais');
        });
    }

    public function modifierTarif(Utilisateur $acteur, string $uuid, int $montantAr): Tarif
    {
        $tarif = $this->frais->tarifOrFail($uuid, (int) $acteur->ecole_id);
        // Only the amount of FUTURE instalments follows; instalments already issued keep their price.
        $tarif->montant_ar = $montantAr;
        $this->frais->saveTarif($tarif);

        return $tarif;
    }

    public function supprimerTarif(Utilisateur $acteur, string $uuid): void
    {
        $this->frais->deleteTarif($this->frais->tarifOrFail($uuid, (int) $acteur->ecole_id));
    }

    private function annee(Utilisateur $acteur, ?string $uuid)
    {
        $ecoleId = (int) $acteur->ecole_id;
        $annee = $uuid !== null ? $this->annees->findInEcoleOrFail($uuid, $ecoleId) : $this->annees->currentOfEcole($ecoleId);
        abort_if($annee === null, 422, 'Aucune année scolaire courante.');

        return $annee;
    }
}
