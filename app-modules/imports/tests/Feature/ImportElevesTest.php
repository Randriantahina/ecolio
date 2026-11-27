<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Enums\Role;
use Modules\Imports\Application\Services\ImportService;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Enums\StatutLigneImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Domain\Models\ImportCorrespondance;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Scolarite\Application\Services\StructureService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

/** @param list<list<scalar|null>> $lignes */
function fichierEleves(array $lignes, string $ext = 'xlsx'): string
{
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.'.$ext;
    $classeur = new Spreadsheet;
    $classeur->getActiveSheet()->fromArray($lignes, null, 'A1');
    (new Xlsx($classeur))->save($chemin);

    return $chemin;
}

const ENTETES = ['Matricule', 'Nom', 'Prénoms', 'Sexe', 'Date de naissance', 'Classe'];

beforeEach(function () {
    Storage::fake('local');
    $this->ecole = Ecole::factory()->create();
    $this->admin = utilisateurAvecRole(Role::Administration, $this->ecole);
    [$this->classeA, $this->classeB] = ecoleAvecClasses($this->ecole, $this->admin);
    dansLePanelEcole($this->admin, $this->ecole);
    $this->service = app(ImportService::class);

    $this->ouvrir = fn (array $lignes, ModeImport $mode = ModeImport::Creation) => $this->service->ouvrir(
        $this->admin, EntiteImport::Eleves, fichierEleves($lignes), 'eleves.xlsx', $mode,
    );
    $this->mapper = function (Import $import, ?array $mapping = null) {
        $mapping ??= $this->service->proposerMapping($this->admin, $import, ENTETES);

        return $this->service->appliquerMapping($this->admin, $import, null, 1, $mapping);
    };
});

it('suggests the mapping from the headers and remembers a manual choice for the next import', function () {
    $import = ($this->ouvrir)([ENTETES]);

    $proposition = $this->service->proposerMapping($this->admin, $import, ['N°', 'Nom', 'Prénoms', 'Genre', 'Né le', 'Classe', 'Quartier']);
    expect($proposition)->toMatchArray(['Nom' => 'nom', 'Prénoms' => 'prenoms', 'Genre' => 'sexe', 'Né le' => 'date_naissance', 'Classe' => 'classe', 'Quartier' => 'ignorer']);

    ($this->mapper)($import, ['Matricule' => 'matricule', 'Nom' => 'nom', 'Prénoms' => 'prenoms', 'Sexe' => 'sexe', 'Date de naissance' => 'date_naissance', 'Classe' => 'classe']);
    $suivant = ($this->ouvrir)([['Mat', 'Nom']]);

    expect(ImportCorrespondance::where('entete', 'sexe')->exists() || ImportCorrespondance::count() > 0)->toBeTrue()
        ->and($this->service->proposerMapping($this->admin, $suivant, ['Matricule', 'Nom']))->toMatchArray(['Matricule' => 'matricule']);
});

it('stages every row without writing a single pupil before the final step (RG-M3)', function () {
    $import = ($this->ouvrir)([ENTETES, ['M-1', 'Rakoto', 'Jean', 'M', '02/03/2014', '6e A'], ['M-2', 'Rabe', 'Lala', 'F', '2014-05-06', '6e B']]);

    $import = ($this->mapper)($import);

    expect($import->statut)->toBe(StatutImport::Valide)->and($import->total)->toBe(2)
        ->and($import->lignes()->count())->toBe(2)->and(Eleve::count())->toBe(0);
});

it('flags the staging errors line by line: empty name, bad date, unknown class, duplicate matricule, bad sex', function () {
    $import = ($this->ouvrir)([
        ENTETES,
        ['M-1', 'Rakoto', 'Jean', 'M', '02/03/2014', '6e A'],
        ['M-1', 'Rabe', 'Lala', 'F', '2014-05-06', '6e B'],
        ['M-3', '', 'Sans nom', 'M', '2014-05-06', '6e A'],
        ['M-4', 'Date', 'Folle', 'M', '31/02/2014', '6e A'],
        ['M-5', 'Classe', 'Inconnue', 'F', '2014-05-06', '9e Z'],
        ['M-6', 'Sexe', 'Bizarre', 'X', '2014-05-06', '6e A'],
    ]);

    $import = ($this->mapper)($import);
    $erreurs = $import->lignes()->get()->mapWithKeys(fn ($l) => [$l->numero => collect($l->erreurs)->pluck('code')->all()]);

    expect($erreurs[2])->toContain('matricule_en_double')->and($erreurs[3])->toContain('matricule_en_double')
        ->and($erreurs[4])->toContain('nom_vide')->and($erreurs[5])->toContain('date_illisible')
        ->and($erreurs[6])->toContain('classe_inconnue')->and($erreurs[7])->toContain('sexe_invalide')
        ->and($import->en_erreur)->toBe(6);
});

it('refuses a file over the 5 000 rows ceiling (RG-M5)', function () {
    $lignes = [ENTETES];
    for ($i = 1; $i <= Import::MAX_LIGNES + 1; $i++) {
        $lignes[] = ["M-$i", "Nom $i", null, null, null, null];
    }
    $import = ($this->ouvrir)($lignes);

    expect(fn () => ($this->mapper)($import))->toThrow(ValidationException::class);
});

it('lets the user fix a cell, exclude a row, or create the missing class', function () {
    $import = ($this->ouvrir)([ENTETES, ['M-1', '', 'Jean', 'M', '2014-03-02', '6e A'], ['M-2', 'Rabe', 'Lala', 'F', '2014-05-06', '9e Z'], ['M-3', 'Bad', 'Row', 'M', '2014-01-01', '6e A']]);
    ($this->mapper)($import);

    $this->service->corriger($this->admin, $import, 2, ['nom' => 'Rakoto']);
    $this->service->exclure($this->admin, $import, 4);
    $niveau = app(StructureService::class)->niveaux($this->admin)->first();
    $this->service->creerClasseManquante($this->admin, $import, '9e Z', $niveau->uuid);

    $lignes = $import->lignes()->get()->keyBy('numero');
    expect($lignes[2]->statut)->toBe(StatutLigneImport::Ok)->and($lignes[3]->statut)->toBe(StatutLigneImport::Ok)
        ->and($lignes[4]->statut)->toBe(StatutLigneImport::Exclue);
});

it('imports in the background, keeps the school matricule and generates one when absent (RG-M1), then reports', function () {
    $import = ($this->ouvrir)([ENTETES, ['SCH-77', 'Rakoto', 'Jean', 'M', '02/03/2014', '6e A'], [null, 'Rabe', 'Lala', 'F', '2014-05-06', '6e B'], ['M-9', '', 'Erreur', 'M', null, null]]);
    ($this->mapper)($import);

    $import = $this->service->lancer($this->admin, $import);

    expect($import->statut)->toBe(StatutImport::Termine)->and($import->crees)->toBe(2)->and($import->en_erreur)->toBe(1)
        ->and(Eleve::pluck('matricule')->all())->toContain('SCH-77')->and(Eleve::whereNull('matricule')->count())->toBe(0)
        ->and(Eleve::where('nom', 'Rabe')->firstOrFail()->inscriptionActive->classe->nom)->toBe('6e B')
        ->and(Eleve::where('nom', 'Rakoto')->firstOrFail()->date_naissance->toDateString())->toBe('2014-03-02');
});

it('maps columns to existing custom fields and creates a new field from an unknown column', function () {
    app(ChampService::class)->creer($this->admin, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);
    $import = ($this->ouvrir)([['Nom', 'Quartier', 'Bourse'], ['Rakoto', 'Analakely', 'oui']]);

    $this->service->appliquerMapping($this->admin, $import, null, 1, ['Nom' => 'nom', 'Quartier' => 'champ:quartier', 'Bourse' => 'nouveau:booleen']);
    $this->service->lancer($this->admin, $import->refresh());

    $eleve = Eleve::firstOrFail();
    expect($eleve->champs_perso)->toMatchArray(['quartier' => 'Analakely', 'bourse' => true]);
});

it('updates by matricule, then by identity only after confirmation (RG-M2)', function () {
    $existant = app(EleveService::class)->creer($this->admin, new CreateEleveDto(
        'Rakoto', 'Jean', 'M', '2014-03-02', null, 'M-1', null, 'nouvelle', false, false,
    ))['eleve'];
    $autre = app(EleveService::class)->creer($this->admin, new CreateEleveDto(
        'Rabe', 'Lala', 'F', '2014-05-06', null, 'M-2', null, 'nouvelle', false, false,
    ))['eleve'];

    $import = ($this->ouvrir)([ENTETES, ['M-1', 'Rakotoson', 'Jean', 'M', '2014-03-02', null], [null, 'Rabe', 'Lala', 'F', '2014-05-06', null]], ModeImport::MiseAJour);
    ($this->mapper)($import);

    $aConfirmer = $import->lignes()->where('numero', 3)->firstOrFail();
    expect(collect($aConfirmer->erreurs)->pluck('code'))->toContain('a_confirmer');
    $this->service->confirmer($this->admin, $import, 3);

    $import = $this->service->lancer($this->admin, $import);

    expect($import->mis_a_jour)->toBe(2)->and($existant->fresh()->nom)->toBe('Rakotoson')->and(Eleve::count())->toBe(2);
});

it('rejects an existing matricule in creation mode', function () {
    app(EleveService::class)->creer($this->admin, new CreateEleveDto(
        'Rakoto', 'Jean', 'M', null, null, 'M-1', null, 'nouvelle', false, false,
    ));
    $import = ($this->ouvrir)([ENTETES, ['M-1', 'Autre', 'Pers', 'M', null, null]]);

    ($this->mapper)($import);

    expect(collect($import->lignes()->first()->erreurs)->pluck('code'))->toContain('matricule_existant');
});

it('rolls a finished import back within 7 days, and refuses after (spec 4.1)', function () {
    $import = ($this->ouvrir)([ENTETES, ['M-1', 'Rakoto', 'Jean', 'M', '2014-03-02', '6e A']]);
    ($this->mapper)($import);
    $import = $this->service->lancer($this->admin, $import);
    expect(Eleve::count())->toBe(1);

    $this->service->annuler($this->admin, $import);
    expect(Eleve::count())->toBe(0)->and($import->fresh()->statut)->toBe(StatutImport::Annule);

    $vieux = ($this->ouvrir)([ENTETES, ['M-2', 'Rabe', 'Lala', 'F', '2014-05-06', '6e A']]);
    ($this->mapper)($vieux);
    $vieux = $this->service->lancer($this->admin, $vieux);
    $vieux->forceFill(['termine_le' => now()->subDays(8)])->save();

    expect(fn () => $this->service->annuler($this->admin, $vieux->refresh()))->toThrow(ValidationException::class);
});

it('is refused to an enseignant', function () {
    $prof = utilisateurAvecRole(Role::Enseignant, $this->ecole);

    expect(fn () => $this->service->ouvrir($prof, EntiteImport::Eleves, fichierEleves([ENTETES]), 'x.xlsx', ModeImport::Creation))
        ->toThrow(AuthorizationException::class);
});

it('never reads or touches the import of another school (tenant isolation)', function () {
    $import = ($this->ouvrir)([ENTETES]);
    $autre = Ecole::factory()->create();
    $adminAutre = utilisateurAvecRole(Role::Administration, $autre);
    dansLePanelEcole($adminAutre, $autre);

    expect(Import::count())->toBe(0)
        ->and(fn () => $this->service->lancer($adminAutre, $import))->toThrow(ModelNotFoundException::class);
});
