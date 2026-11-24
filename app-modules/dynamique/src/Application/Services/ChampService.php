<?php

declare(strict_types=1);

namespace Modules\Dynamique\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Domain\Contracts\ChampRepository;
use Modules\Dynamique\Domain\Contracts\LecteurValeursChamps;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;
use Modules\Identity\Domain\Models\Utilisateur;

/** Custom columns (spec section 3): definitions, and validation of the values they receive. */
final class ChampService
{
    /** RG-D4 [Hypothèse]: protects performance and readability. */
    public const MAX_ACTIFS = 30;

    /** @param  iterable<LecteurValeursChamps>  $lecteurs */
    public function __construct(
        private readonly ChampRepository $champs,
        private readonly iterable $lecteurs = [],
    ) {}

    /** @return Collection<int, ChampPersonnalise> */
    public function lister(Utilisateur $acteur, EntiteChamp $entite, bool $actifsSeulement = true): Collection
    {
        return $this->champs->lister((int) $acteur->ecole_id, $entite, $actifsSeulement);
    }

    /** @param  list<string>|null  $options */
    public function creer(Utilisateur $acteur, EntiteChamp $entite, string $libelle, TypeChamp $type, ?array $options = null, bool $obligatoire = false): ChampPersonnalise
    {
        return DB::transaction(function () use ($acteur, $entite, $libelle, $type, $options, $obligatoire): ChampPersonnalise {
            $ecoleId = (int) $acteur->ecole_id;

            if ($this->champs->compterActifs($ecoleId, $entite) >= self::MAX_ACTIFS) {
                throw ValidationException::withMessages(['libelle' => 'Maximum '.self::MAX_ACTIFS.' champs personnalisés actifs : désactivez-en un d\'abord.']);
            }

            $champ = new ChampPersonnalise(['libelle' => $libelle, 'obligatoire' => $obligatoire]);
            $champ->ecole_id = $ecoleId;
            $champ->entite = $entite;
            $champ->type = $type;
            $champ->options = $this->options($type, $options);
            $champ->cle = $this->cleUnique($ecoleId, $entite, $libelle);
            $champ->ordre = $this->champs->prochainOrdre($ecoleId, $entite);
            $this->champs->save($champ);

            return $champ;
        });
    }

    /** RG-D2: only the label changes, the technical key never does. */
    public function renommer(Utilisateur $acteur, string $uuid, string $libelle): ChampPersonnalise
    {
        $champ = $this->champs->findOrFail($uuid, (int) $acteur->ecole_id);
        $champ->libelle = $libelle;
        $this->champs->save($champ);

        return $champ;
    }

    public function definirObligatoire(Utilisateur $acteur, string $uuid, bool $obligatoire): ChampPersonnalise
    {
        $champ = $this->champs->findOrFail($uuid, (int) $acteur->ecole_id);
        $champ->obligatoire = $obligatoire;
        $this->champs->save($champ);

        return $champ;
    }

    /** @param  list<string>|null  $options */
    public function changerType(Utilisateur $acteur, string $uuid, TypeChamp $type, ?array $options): ChampPersonnalise
    {
        $ecoleId = (int) $acteur->ecole_id;
        $champ = $this->champs->findOrFail($uuid, $ecoleId);
        $options = $this->options($type, $options);

        // RG-D3: allowed only when every stored value converts, otherwise name the rows in the way.
        $fautifs = [];
        foreach ($this->lecteurs as $lecteur) {
            if (! $lecteur->supporte($champ->entite)) {
                continue;
            }
            foreach ($lecteur->valeurs($ecoleId, $champ->entite, $champ->cle) as $ligne => $valeur) {
                if (! $this->valide($type, $options, $valeur)) {
                    $fautifs[] = $ligne;
                }
            }
        }
        if ($fautifs !== []) {
            throw ValidationException::withMessages(['type' => 'Valeurs non convertibles : '.implode(', ', array_slice($fautifs, 0, 20)).(count($fautifs) > 20 ? '…' : '')]);
        }

        $champ->type = $type;
        $champ->options = $options;
        $this->champs->save($champ);

        return $champ;
    }

    /** "Delete" only deactivates: the data stays stored (spec 3.2). */
    public function desactiver(Utilisateur $acteur, string $uuid): ChampPersonnalise
    {
        $champ = $this->champs->findOrFail($uuid, (int) $acteur->ecole_id);
        $champ->actif = false;
        $champ->desactive_le = now();
        $this->champs->save($champ);

        return $champ;
    }

    public function reactiver(Utilisateur $acteur, string $uuid): ChampPersonnalise
    {
        $ecoleId = (int) $acteur->ecole_id;
        $champ = $this->champs->findOrFail($uuid, $ecoleId);
        if ($this->champs->compterActifs($ecoleId, $champ->entite) >= self::MAX_ACTIFS) {
            throw ValidationException::withMessages(['actif' => 'Maximum '.self::MAX_ACTIFS.' champs personnalisés actifs.']);
        }
        $champ->actif = true;
        $champ->desactive_le = null;
        $this->champs->save($champ);

        return $champ;
    }

    /**
     * RG-D6: values are validated server-side from the school's definitions. Unknown or deactivated keys
     * are dropped silently (a stale form must not crash), mandatory ones are enforced.
     *
     * @param  array<string, mixed>  $valeurs
     * @return array<string, mixed> cleaned and cast
     */
    public function valider(Utilisateur $acteur, EntiteChamp $entite, array $valeurs): array
    {
        $definitions = $this->champs->lister((int) $acteur->ecole_id, $entite, true);
        $regles = [];
        $noms = [];
        foreach ($definitions as $champ) {
            $regles["champs_perso.{$champ->cle}"] = $this->regles($champ);
            $noms["champs_perso.{$champ->cle}"] = $champ->libelle;
        }

        $valides = Validator::make(['champs_perso' => $valeurs], $regles, [], $noms)->validate();

        $propre = [];
        foreach ($definitions as $champ) {
            $valeur = $valides['champs_perso'][$champ->cle] ?? null;
            if ($valeur === null || $valeur === '') {
                continue;
            }
            $propre[$champ->cle] = match ($champ->type) {
                TypeChamp::Booleen => (bool) $valeur,
                TypeChamp::Nombre => $valeur + 0,
                default => $valeur,
            };
        }

        return $propre;
    }

    /** @return list<mixed> Laravel rules for one definition. */
    private function regles(ChampPersonnalise $champ): array
    {
        $regles = [$champ->obligatoire ? 'required' : 'nullable'];

        return array_merge($regles, match ($champ->type) {
            TypeChamp::Texte => ['string', 'max:255'],
            TypeChamp::Nombre => ['numeric'],
            TypeChamp::Date => ['date_format:Y-m-d'],
            TypeChamp::Booleen => ['boolean'],
            TypeChamp::Liste => [Rule::in($champ->options ?? [])],
            TypeChamp::Telephone => ['regex:/^[0-9+() .\-]{6,20}$/'],
        });
    }

    /** @param  list<string>|null  $options */
    private function valide(TypeChamp $type, ?array $options, mixed $valeur): bool
    {
        $regles = $this->regles($this->fantome($type, $options));

        return ! Validator::make(['v' => $valeur], ['v' => $regles])->fails();
    }

    /** @param  list<string>|null  $options */
    private function fantome(TypeChamp $type, ?array $options): ChampPersonnalise
    {
        $champ = new ChampPersonnalise(['obligatoire' => false]);
        $champ->type = $type;
        $champ->options = $options;

        return $champ;
    }

    /**
     * @param  list<string>|null  $options
     * @return list<string>|null
     */
    private function options(TypeChamp $type, ?array $options): ?array
    {
        if ($type !== TypeChamp::Liste) {
            return null;
        }
        $options = array_values(array_filter(array_map('trim', $options ?? []), fn (string $o): bool => $o !== ''));
        if ($options === []) {
            throw ValidationException::withMessages(['options' => 'Une liste de choix a besoin d\'au moins une option.']);
        }

        return $options;
    }

    private function cleUnique(int $ecoleId, EntiteChamp $entite, string $libelle): string
    {
        $base = Str::slug($libelle, '_') ?: 'champ';
        $cle = $base;
        for ($i = 2; $this->champs->cleExiste($ecoleId, $entite, $cle); $i++) {
            $cle = "{$base}_{$i}";
        }

        return $cle;
    }
}
