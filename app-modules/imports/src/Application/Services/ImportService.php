<?php

declare(strict_types=1);

namespace Modules\Imports\Application\Services;

use Illuminate\Database\QueryException;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Application\DTOs\UpdateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Imports\Application\DTOs\ContexteValidation;
use Modules\Imports\Domain\Contracts\ImportRepository;
use Modules\Imports\Domain\Contracts\LecteurFichier;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Enums\StatutLigneImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Domain\Models\ImportLigne;
use Modules\Imports\Jobs\ExecuterImportJob;
use Modules\Scolarite\Application\Services\StructureService;

/**
 * The 5-step wizard of spec 4.1. RG-M3: every method before `lancer` only touches `imports`,
 * `import_lignes` and `import_correspondances`, never the pupils.
 */
final class ImportService
{
    private const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /** Targets a header can be mapped to besides "champ:<cle>" and "nouveau:<type>". */
    public const CIBLES = ['matricule', 'nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance', 'classe', 'ignorer'];

    private const SYNONYMES = [
        'matricule' => ['matricule', 'mat', 'numero', 'n', 'nmatricule', 'nodordre'],
        'nom' => ['nom', 'nomdefamille', 'lastname', 'nomeleve'],
        'prenoms' => ['prenom', 'prenoms', 'firstname'],
        'sexe' => ['sexe', 'genre', 'fg'],
        'date_naissance' => ['datedenaissance', 'datenaissance', 'naissance', 'nele', 'dob', 'datenaiss'],
        'lieu_naissance' => ['lieudenaissance', 'lieunaissance', 'lieu'],
        'classe' => ['classe', 'class'],
    ];

    public function __construct(
        private readonly ImportRepository $imports,
        private readonly LecteurFichier $lecteur,
        private readonly ValidationLigneEleve $validation,
        private readonly ChampService $champs,
        private readonly StructureService $structure,
        private readonly EleveService $eleves,
        private readonly DevineType $devineType,
    ) {}

    /** Step 1: keep the file and open the batch. */
    public function ouvrir(Utilisateur $acteur, EntiteImport $entite, string $cheminFichier, string $nomOriginal, ModeImport $mode): Import
    {
        $this->autoriser($acteur, 'create');

        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONS, true)) {
            throw ValidationException::withMessages(['fichier' => 'Format non pris en charge : .xlsx, .xls ou .csv.']);
        }
        if ($entite !== EntiteImport::Eleves) {
            // RG-M6: tutors and historical payments come with their own phases.
            throw ValidationException::withMessages(['entite' => 'Seul l\'import des élèves est disponible pour l\'instant.']);
        }

        $chemin = Storage::disk('local')->putFileAs("imports/{$acteur->ecole_id}", new File($cheminFichier), Str::uuid()->toString().'.'.$extension);

        $import = new Import([
            'entite' => $entite, 'mode' => $mode, 'fichier_path' => $chemin, 'nom_original' => $nomOriginal,
            'statut' => StatutImport::Envoye, 'ligne_entete' => 1,
        ]);
        $import->ecole_id = $acteur->ecole_id;
        $import->utilisateur_id = $acteur->id;
        $this->imports->save($import);

        return $import;
    }

    public function retrouver(Utilisateur $acteur, string $uuid): Import
    {
        $this->autoriser($acteur, 'viewAny');

        return $this->imports->findOrFail($uuid, (int) $acteur->ecole_id);
    }

    /** @return list<string> */
    public function feuilles(Import $import): array
    {
        return $this->lecteur->feuilles($this->chemin($import));
    }

    /**
     * Step 1 preview: the header candidates and the first rows after them.
     *
     * @return array{entetes: list<string>, lignes: list<list<scalar|null>>, avertissements: list<string>}
     */
    public function apercu(Import $import, ?string $feuille, int $ligneEntete, int $nombre = 5): array
    {
        $lecture = $this->lecteur->lire($this->chemin($import), $feuille);

        return [
            'entetes' => $this->entetes($lecture->lignes, $ligneEntete),
            'lignes' => array_values(array_slice(array_filter($lecture->lignes, fn ($_, $n) => $n > $ligneEntete, ARRAY_FILTER_USE_BOTH), 0, $nombre)),
            'avertissements' => $lecture->avertissements,
        ];
    }

    /**
     * Step 2 suggestion: remembered choices first, then well-known header names, then custom fields by label.
     *
     * @param  list<string>  $entetes
     * @return array<string, string> header => target
     */
    public function proposerMapping(Utilisateur $acteur, Import $import, array $entetes): array
    {
        $memorisees = $this->imports->correspondances((int) $acteur->ecole_id, $import->entite);
        $champs = $this->champs->lister($acteur, EntiteChamp::Eleve)->mapWithKeys(fn ($c) => [ValidationLigneEleve::cle($c->libelle) => 'champ:'.$c->cle]);

        $deja = [];
        $mapping = [];
        foreach ($entetes as $entete) {
            $cle = ValidationLigneEleve::cle($entete);
            $cible = $memorisees[$cle] ?? $this->synonyme($cle) ?? ($champs[$cle] ?? null) ?? 'ignorer';
            // One column per target: the second "Nom" must not silently override the first.
            if ($cible !== 'ignorer' && in_array($cible, $deja, true)) {
                $cible = 'ignorer';
            }
            $deja[] = $cible;
            $mapping[$entete] = $cible;
        }

        return $mapping;
    }

    /** Type guessed from the column's first values, for the "new field" choice. */
    public function typeDevine(Import $import, ?string $feuille, int $ligneEntete, string $entete): TypeChamp
    {
        $lecture = $this->lecteur->lire($this->chemin($import), $feuille);
        $index = array_search($entete, $this->entetes($lecture->lignes, $ligneEntete), true);
        $echantillon = [];
        foreach ($lecture->lignes as $numero => $cellules) {
            if ($numero > $ligneEntete && $index !== false) {
                $echantillon[] = $cellules[$index] ?? null;
            }
            if (count($echantillon) >= 50) {
                break;
            }
        }

        return $this->devineType->pour($echantillon);
    }

    /**
     * Steps 2→3: fix the mapping, read the whole file into staging and judge every row.
     *
     * @param  array<string, string>  $mapping  header => target (see CIBLES, "champ:<cle>", "nouveau:<type>")
     */
    public function appliquerMapping(Utilisateur $acteur, Import $import, ?string $feuille, int $ligneEntete, array $mapping): Import
    {
        $this->autoriser($acteur, 'create');
        $import = $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id);

        $lecture = $this->lecteur->lire($this->chemin($import), $feuille);
        $entetes = $this->entetes($lecture->lignes, $ligneEntete);
        $donnees = array_filter($lecture->lignes, fn ($_, $n) => $n > $ligneEntete, ARRAY_FILTER_USE_BOTH);

        // RG-M5
        if (count($donnees) > Import::MAX_LIGNES) {
            throw ValidationException::withMessages(['fichier' => 'Plus de '.Import::MAX_LIGNES.' lignes : découpez le fichier en plusieurs imports.']);
        }
        $this->verifierMapping($mapping);

        return DB::transaction(function () use ($acteur, $import, $feuille, $ligneEntete, $mapping, $entetes, $donnees): Import {
            $colonnes = $this->resoudreColonnes($acteur, $mapping, $entetes);

            foreach ($mapping as $entete => $cible) {
                $this->imports->memoriserCorrespondance((int) $acteur->ecole_id, $import->entite, ValidationLigneEleve::cle($entete), $colonnes[$entete] ?? 'ignorer');
            }

            $brutes = [];
            foreach ($donnees as $numero => $cellules) {
                $brut = [];
                foreach ($entetes as $i => $entete) {
                    $cible = $colonnes[$entete] ?? 'ignorer';
                    if ($cible !== 'ignorer') {
                        $brut[$cible] = $cellules[$i] ?? null;
                    }
                }
                $brutes[$numero] = $brut;
            }

            $import->forceFill(['feuille' => $feuille, 'ligne_entete' => $ligneEntete, 'mapping' => $colonnes, 'total' => count($brutes)]);
            $this->imports->remplacerLignes($import, array_map(fn ($numero, $brut) => [
                'numero' => $numero, 'donnees' => $brut, 'erreurs' => [], 'statut' => StatutLigneImport::Ok->value, 'action' => null, 'enregistrement_id' => null,
            ], array_keys($brutes), array_values($brutes)));

            return $this->revalider($acteur, $import);
        });
    }

    /** @return Collection<int, ImportLigne> */
    public function lignes(Utilisateur $acteur, Import $import, ?StatutLigneImport $statut = null): Collection
    {
        $this->autoriser($acteur, 'viewAny');

        return $this->imports->lignes($this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id), $statut?->value);
    }

    /** Step 4: edit a cell of the staged row. */
    public function corriger(Utilisateur $acteur, Import $import, int $numero, array $donnees): void
    {
        $this->modifierLigne($acteur, $import, $numero, function (ImportLigne $ligne) use ($donnees): void {
            $ligne->donnees = array_merge($ligne->donnees, $donnees);
            $ligne->confirmee = false;
        });
    }

    public function exclure(Utilisateur $acteur, Import $import, int $numero, bool $exclure = true): void
    {
        $this->modifierLigne($acteur, $import, $numero, function (ImportLigne $ligne) use ($exclure): void {
            $ligne->statut = $exclure ? StatutLigneImport::Exclue : StatutLigneImport::Ok;
        });
    }

    /** Accepts a probable twin (creation) or an identity match (update, RG-M2). */
    public function confirmer(Utilisateur $acteur, Import $import, int $numero): void
    {
        $this->modifierLigne($acteur, $import, $numero, fn (ImportLigne $ligne) => $ligne->confirmee = true);
    }

    /** Step 4: "create the missing class". */
    public function creerClasseManquante(Utilisateur $acteur, Import $import, string $nomClasse, string $niveauUuid): void
    {
        $this->autoriser($acteur, 'create');
        $this->structure->creerClasse($acteur, ['nom' => $nomClasse, 'niveau_uuid' => $niveauUuid]);
        $this->revalider($acteur, $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id));
    }

    /** Step 5: run in the background. */
    public function lancer(Utilisateur $acteur, Import $import): Import
    {
        $this->autoriser($acteur, 'create');
        $import = $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id);

        if ($import->statut !== StatutImport::Valide) {
            throw ValidationException::withMessages(['import' => 'Cet import n\'est pas prêt : refaites la correspondance des colonnes.']);
        }

        $import->statut = StatutImport::EnCours;
        $this->imports->save($import);
        ExecuterImportJob::dispatch((int) $acteur->ecole_id, $acteur->id, $import->uuid);

        return $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id);
    }

    /** Spec 4.1 step 5: undo a whole batch for 7 days. All or nothing. */
    public function annuler(Utilisateur $acteur, Import $import): void
    {
        $this->autoriser($acteur, 'delete');
        $import = $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id);

        if (! $import->peutEtreAnnule()) {
            throw ValidationException::withMessages(['import' => 'Cet import ne peut plus être annulé (terminé depuis plus de '.Import::JOURS_ANNULATION.' jours, ou déjà annulé).']);
        }

        try {
            DB::transaction(function () use ($acteur, $import): void {
                foreach ($this->imports->lignes($import, StatutLigneImport::Importee->value) as $ligne) {
                    $ligne->action === 'creer'
                        ? $this->eleves->supprimerDefinitivement($acteur, $ligne->avant['uuid'])
                        : $this->eleves->modifier($acteur, $ligne->avant['uuid'], $this->etatAvant($ligne));
                    $ligne->statut = StatutLigneImport::Ok;
                    $this->imports->saveLigne($ligne);
                }
                $import->statut = StatutImport::Annule;
                $this->imports->save($import);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['import' => 'Annulation impossible : certains élèves importés ont déjà des paiements.']);
        }
    }

    /** Recomputes every row (file-wide duplicates and class lookups depend on the other rows). */
    public function revalider(Utilisateur $acteur, Import $import): Import
    {
        $lignes = $this->imports->lignes($import);
        $contexte = $this->contexte($acteur, $import, $lignes->pluck('donnees')->all());

        foreach ($lignes as $ligne) {
            if ($ligne->statut === StatutLigneImport::Exclue || $ligne->statut === StatutLigneImport::Importee) {
                continue;
            }
            $resultat = $this->validation->valider($acteur, $ligne->donnees, $contexte);
            $ligne->erreurs = $resultat->erreurs;
            $ligne->action = $resultat->action;
            $ligne->enregistrement_id = $resultat->cibleId;
            $ligne->statut = $resultat->estBloquant($ligne->confirmee) ? StatutLigneImport::Erreur : StatutLigneImport::Ok;
            $this->imports->saveLigne($ligne);
        }

        $import->statut = StatutImport::Valide;
        $import->en_erreur = $lignes->filter(fn (ImportLigne $l) => $l->statut === StatutLigneImport::Erreur)->count();
        $import->ignores = $lignes->filter(fn (ImportLigne $l) => $l->statut === StatutLigneImport::Exclue)->count();
        $this->imports->save($import);

        return $import;
    }

    /** @param list<array<string, mixed>> $brutes */
    public function contexte(Utilisateur $acteur, Import $import, array $brutes): ContexteValidation
    {
        $classes = $this->structure->classes($acteur, null)->mapWithKeys(fn ($c) => [ValidationLigneEleve::cle($c->nom) => $c->uuid])->all();
        $champs = $this->champs->lister($acteur, EntiteChamp::Eleve)->mapWithKeys(fn ($c) => [$c->cle => $c->type])->all();
        $matricules = array_count_values(array_filter(array_map(fn (array $b) => ($m = trim((string) ($b['matricule'] ?? ''))) === '' ? null : $m, $brutes)));

        return new ContexteValidation($import->mode, $classes, $champs, $matricules);
    }

    private function modifierLigne(Utilisateur $acteur, Import $import, int $numero, callable $modifier): void
    {
        $this->autoriser($acteur, 'create');
        $import = $this->imports->findOrFail($import->uuid, (int) $acteur->ecole_id);
        $ligne = $this->imports->ligneOrFail($import, $numero);
        $modifier($ligne);
        $this->imports->saveLigne($ligne);
        $this->revalider($acteur, $import);
    }

    private function etatAvant(ImportLigne $ligne): UpdateEleveDto
    {
        $a = $ligne->avant;

        return new UpdateEleveDto($a['nom'], $a['prenoms'], $a['sexe'], $a['date_naissance'], $a['lieu_naissance'], null, $a['champs_perso'] ?? []);
    }

    /** @param array<string, string> $mapping */
    private function verifierMapping(array $mapping): void
    {
        $cibles = array_values(array_filter($mapping, fn (string $c) => $c !== 'ignorer' && ! str_starts_with($c, 'nouveau:')));

        if (! in_array('nom', $cibles, true)) {
            throw ValidationException::withMessages(['mapping' => 'Associez au moins une colonne au nom de l\'élève.']);
        }
        if (count($cibles) !== count(array_unique($cibles))) {
            throw ValidationException::withMessages(['mapping' => 'Deux colonnes du fichier ne peuvent pas aller dans le même champ.']);
        }
        foreach ($mapping as $cible) {
            if (! in_array($cible, self::CIBLES, true) && ! str_starts_with($cible, 'champ:') && ! str_starts_with($cible, 'nouveau:')) {
                throw ValidationException::withMessages(['mapping' => "Champ de destination inconnu : $cible."]);
            }
        }
    }

    /**
     * Turns "nouveau:<type>" into a real custom field (the only thing step 2 writes outside staging)
     * and returns header => final target.
     *
     * @param  array<string, string>  $mapping
     * @param  list<string>  $entetes
     * @return array<string, string>
     */
    private function resoudreColonnes(Utilisateur $acteur, array $mapping, array $entetes): array
    {
        $colonnes = [];
        foreach ($entetes as $entete) {
            $cible = $mapping[$entete] ?? 'ignorer';
            if (str_starts_with($cible, 'nouveau:')) {
                $type = TypeChamp::tryFrom(substr($cible, 8)) ?? TypeChamp::Texte;
                $type = $type === TypeChamp::Liste ? TypeChamp::Texte : $type;
                $cible = 'champ:'.$this->champs->creer($acteur, EntiteChamp::Eleve, $entete, $type)->cle;
            }
            $colonnes[$entete] = $cible;
        }

        return $colonnes;
    }

    /**
     * @param  array<int, list<scalar|null>>  $lignes
     * @return list<string>
     */
    private function entetes(array $lignes, int $ligneEntete): array
    {
        $cellules = $lignes[$ligneEntete] ?? [];
        $largeur = max(array_map('count', $lignes) ?: [0]);

        return array_map(fn (int $i): string => trim((string) ($cellules[$i] ?? '')) ?: 'Colonne '.($i + 1), range(0, max($largeur, count($cellules)) - 1));
    }

    private function synonyme(string $cle): ?string
    {
        foreach (self::SYNONYMES as $cible => $noms) {
            if (in_array($cle, $noms, true)) {
                return $cible;
            }
        }

        return null;
    }

    private function chemin(Import $import): string
    {
        return Storage::disk('local')->path($import->fichier_path);
    }

    private function autoriser(Utilisateur $acteur, string $ability): void
    {
        Gate::forUser($acteur)->authorize($ability, Import::class);
    }
}
