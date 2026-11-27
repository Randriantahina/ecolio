<?php

declare(strict_types=1);

namespace Modules\Imports\Application\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Imports\Application\DTOs\ContexteValidation;
use Modules\Imports\Application\DTOs\ResultatLigne;
use Modules\Imports\Domain\Enums\ModeImport;

/** Judges one staged pupil row (spec 4.1 step 3): never throws, every problem becomes an error on the row. */
final class ValidationLigneEleve
{
    private const FORMATS_DATE = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y'];

    public function __construct(private readonly EleveService $eleves, private readonly ChampService $champs) {}

    /** Header text reduced to letters and digits, so "Prénoms", "prenoms " and "PRENOMS" are one thing. */
    public static function cle(string $texte): string
    {
        return (string) Str::of($texte)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '');
    }

    /** @param array<string, mixed> $brut cell values by target key */
    public function valider(Utilisateur $acteur, array $brut, ContexteValidation $contexte): ResultatLigne
    {
        $erreurs = [];
        $erreur = function (string $champ, string $code, string $message) use (&$erreurs): void {
            $erreurs[] = ['champ' => $champ, 'code' => $code, 'message' => $message];
        };

        $texte = fn (string $cle): ?string => ($v = trim((string) ($brut[$cle] ?? ''))) === '' ? null : $v;

        $nom = $texte('nom');
        $matricule = $texte('matricule');

        if ($nom === null) {
            $erreur('nom', 'nom_vide', 'Le nom est obligatoire.');
        } elseif (str_starts_with(self::cle($nom), 'total')) {
            // RG-M4: a totals line is reported, not imported as a pupil called "Total".
            $erreur('nom', 'ligne_total', 'Ligne de total ou de titre : excluez-la.');
        }

        if ($matricule !== null && ($contexte->matriculesFichier[$matricule] ?? 0) > 1) {
            $erreur('matricule', 'matricule_en_double', 'Ce matricule apparaît plusieurs fois dans le fichier.');
        }

        $dateNaissance = null;
        if (($brutDate = $texte('date_naissance')) !== null) {
            $dateNaissance = $this->date($brutDate);
            $dateNaissance ?? $erreur('date_naissance', 'date_illisible', "Date illisible : « $brutDate ».");
        }

        $sexe = null;
        if (($brutSexe = $texte('sexe')) !== null) {
            $sexe = $this->sexe($brutSexe);
            $sexe ?? $erreur('sexe', 'sexe_invalide', "Sexe non reconnu : « $brutSexe » (F ou M).");
        }

        $classeUuid = null;
        if (($brutClasse = $texte('classe')) !== null) {
            $classeUuid = $contexte->classes[self::cle($brutClasse)] ?? null;
            $classeUuid ?? $erreur('classe', 'classe_inconnue', "Classe inconnue : « $brutClasse ».");
        }

        $champsPerso = $this->champsPerso($acteur, $brut, $contexte, $erreur);

        [$action, $cible] = $this->cible($acteur, $contexte->mode, $nom, $texte('prenoms'), $dateNaissance, $matricule, $erreur);

        return new ResultatLigne([
            'matricule' => $matricule, 'nom' => $nom, 'prenoms' => $texte('prenoms'), 'sexe' => $sexe,
            'date_naissance' => $dateNaissance, 'lieu_naissance' => $texte('lieu_naissance'),
            'classe_uuid' => $classeUuid, 'champs_perso' => $champsPerso,
        ], $erreurs, $action, $cible?->id, $cible?->uuid);
    }

    /**
     * RG-M2: update mode matches by matricule, else by identity but only once a human confirms it.
     * Creation mode refuses a known matricule and asks before creating a probable twin.
     *
     * @return array{0: 'creer'|'mettre_a_jour', 1: ?Eleve}
     */
    private function cible(Utilisateur $acteur, ModeImport $mode, ?string $nom, ?string $prenoms, ?string $date, ?string $matricule, callable $erreur): array
    {
        $parMatricule = $matricule !== null ? $this->eleves->parMatricule($acteur, $matricule) : null;

        if ($mode === ModeImport::Creation) {
            if ($matricule !== null && $this->eleves->matriculeExiste($acteur, $matricule)) {
                $erreur('matricule', 'matricule_existant', 'Ce matricule existe déjà dans l\'école.');
            } elseif ($nom !== null && $this->eleves->parIdentite($acteur, $nom, $prenoms, $date) !== null) {
                $erreur('nom', 'doublon', 'Un élève de même nom et date de naissance existe déjà : confirmez pour l\'importer quand même.');
            }

            return ['creer', null];
        }

        if ($parMatricule !== null) {
            return ['mettre_a_jour', $parMatricule];
        }
        if ($nom !== null && ($parIdentite = $this->eleves->parIdentite($acteur, $nom, $prenoms, $date)) !== null) {
            $erreur('nom', 'a_confirmer', 'Aucun matricule trouvé, mais un élève de même identité existe : confirmez la correspondance.');

            return ['mettre_a_jour', $parIdentite];
        }

        return ['creer', null];
    }

    /** @return array<string, mixed> */
    private function champsPerso(Utilisateur $acteur, array $brut, ContexteValidation $contexte, callable $erreur): array
    {
        $valeurs = [];
        foreach ($contexte->champs as $cle => $type) {
            $v = $brut["champ:$cle"] ?? null;
            if ($v === null || trim((string) $v) === '') {
                continue;
            }
            $valeurs[$cle] = match ($type) {
                TypeChamp::Booleen => $this->booleen((string) $v) ?? $v,
                TypeChamp::Date => $this->date((string) $v) ?? $v,
                default => is_string($v) ? trim($v) : $v,
            };
        }

        try {
            return $this->champs->valider($acteur, EntiteChamp::Eleve, $valeurs);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $erreur(str_replace('champs_perso.', 'champ:', $champ), 'champ_invalide', $messages[0]);
            }

            return [];
        }
    }

    public function date(string $texte): ?string
    {
        foreach (self::FORMATS_DATE as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, trim($texte));
            // createFromFormat accepts 31/02 and rolls it over: comparing back catches the impossible date.
            if ($date !== false && $date->format($format) === trim($texte)) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function sexe(string $texte): ?string
    {
        return match (self::cle($texte)) {
            'f', 'feminin', 'fille', 'femme' => 'F',
            'm', 'masculin', 'garcon', 'homme' => 'M',
            default => null,
        };
    }

    private function booleen(string $texte): ?bool
    {
        return match (self::cle($texte)) {
            'oui', 'o', 'true', 'vrai', '1', 'x' => true,
            'non', 'n', 'false', 'faux', '0' => false,
            default => null,
        };
    }
}
