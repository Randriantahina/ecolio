<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Enums\Role;
use Modules\Imports\Application\Services\ImportService;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Filament\Pages\ImporterEleves;
use Modules\Imports\Filament\Resources\ImportResource;
use Modules\Imports\Filament\Resources\Pages\ControlerImport;
use Modules\Imports\Filament\Resources\Pages\ListImports;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Scolarite\Application\Services\StructureService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function cheminClasseur(array $lignes): string
{
    $chemin = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    $classeur = new Spreadsheet;
    $classeur->getActiveSheet()->fromArray($lignes, null, 'A1');
    (new Xlsx($classeur))->save($chemin);

    return $chemin;
}

/** Filament's fake-upload testing needs Laravel's fake File, which still carries real spreadsheet bytes. */
function classeurTemporaire(array $lignes): File
{
    return UploadedFile::fake()->createWithContent('eleves.xlsx', (string) file_get_contents(cheminClasseur($lignes)));
}

beforeEach(function () {
    Storage::fake('local');
    $this->ecole = Ecole::factory()->create();
    $this->admin = utilisateurAvecRole(Role::Administration, $this->ecole);
    [$this->classeA] = ecoleAvecClasses($this->ecole, $this->admin);
    dansLePanelEcole($this->admin, $this->ecole);

    $this->importer = fn (array $lignes) => app(ImportService::class)->appliquerMapping(
        $this->admin,
        app(ImportService::class)->ouvrir($this->admin, EntiteImport::Eleves, (function () use ($lignes) {
            $f = classeurTemporaire($lignes);

            return $f->getRealPath();
        })(), 'eleves.xlsx', ModeImport::Creation),
        null, 1, ['Nom' => 'nom', 'Matricule' => 'matricule', 'Classe' => 'classe'],
    );
});

it('walks the wizard from the file to the control screen without writing any pupil', function () {
    $page = Livewire::test(ImporterEleves::class)
        ->fillForm(['mode' => 'creation', 'fichier' => classeurTemporaire([['Matricule', 'Nom', 'Prénoms', 'Classe'], ['M-1', 'Rakoto', 'Jean', '6e A']])])
        ->goToNextWizardStep()->assertHasNoFormErrors()
        ->fillForm(['feuille' => 'Worksheet', 'ligne_entete' => 1])
        ->goToNextWizardStep()->assertHasNoFormErrors();

    expect($page->get('data.mapping'))->toMatchArray(['c0' => 'matricule', 'c1' => 'nom', 'c2' => 'prenoms', 'c3' => 'classe']);

    $page->call('controler')->assertRedirect();
    $import = Import::firstOrFail();

    expect($import->statut)->toBe(StatutImport::Valide)->and($import->total)->toBe(1)->and(Eleve::count())->toBe(0);
});

it('asks for at least the name column', function () {
    Livewire::test(ImporterEleves::class)
        ->fillForm(['mode' => 'creation', 'fichier' => classeurTemporaire([['Matricule', 'Classe'], ['M-1', '6e A']])])
        ->goToNextWizardStep()->fillForm(['feuille' => 'Worksheet', 'ligne_entete' => 1])->goToNextWizardStep()
        ->call('controler')->assertHasErrors(['data.mapping']);
});

it('lists the imports of the school and opens one', function () {
    $import = ($this->importer)([['Matricule', 'Nom', 'Classe'], ['M-1', 'Rakoto', '6e A']]);

    Livewire::test(ListImports::class)->assertCanSeeTableRecords(Import::all())->assertSee('eleves.xlsx');
    $this->get(ImportResource::getUrl('controle', ['record' => $import]))->assertOk();
});

it('shows the staged rows with their problems and lets the user fix, exclude and confirm', function () {
    $import = ($this->importer)([['Matricule', 'Nom', 'Classe'], ['M-1', '', '6e A'], ['M-2', 'Rabe', '9e Z']]);

    $page = Livewire::test(ControlerImport::class, ['record' => $import->uuid])
        ->assertSee('Le nom est obligatoire.')->assertSee('Classe inconnue');

    $page->callTableAction('corriger', $import->lignes()->where('numero', 2)->first(), ['matricule' => 'M-1', 'nom' => 'Rakoto', 'classe' => '6e A'])
        ->callTableAction('exclure', $import->lignes()->where('numero', 3)->first());

    expect($import->lignes()->where('numero', 2)->first()->statut->value)->toBe('ok')
        ->and($import->lignes()->where('numero', 3)->first()->statut->value)->toBe('exclue');
});

it('runs the import from the control screen and then rolls it back', function () {
    $import = ($this->importer)([['Matricule', 'Nom', 'Classe'], ['M-1', 'Rakoto', '6e A']]);

    Livewire::test(ControlerImport::class, ['record' => $import->uuid])->callAction('lancer')->assertNotified('Import lancé');

    expect($import->fresh()->statut)->toBe(StatutImport::Termine)->and(Eleve::where('matricule', 'M-1')->exists())->toBeTrue();

    Livewire::test(ControlerImport::class, ['record' => $import->uuid])->callAction('annuler')->assertNotified('Import annulé');
    expect(Eleve::count())->toBe(0);
});

it('creates the missing class from the control screen', function () {
    $import = ($this->importer)([['Matricule', 'Nom', 'Classe'], ['M-1', 'Rakoto', '9e Z']]);
    $niveau = app(StructureService::class)->niveaux($this->admin)->first();

    Livewire::test(ControlerImport::class, ['record' => $import->uuid])
        ->callAction('creerClasse', ['nom' => '9e Z', 'niveau_uuid' => $niveau->uuid]);

    expect($import->lignes()->first()->statut->value)->toBe('ok');
});

it('keeps the import pages away from enseignants', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);

    expect(ImportResource::canViewAny())->toBeFalse()->and(ImporterEleves::canAccess())->toBeFalse();
});

it('never shows the imports of another school (tenant isolation)', function () {
    $import = ($this->importer)([['Matricule', 'Nom', 'Classe'], ['M-1', 'Rakoto', '6e A']]);
    $autre = Ecole::factory()->create();
    dansLePanelEcole(utilisateurAvecRole(Role::Administration, $autre), $autre);

    Livewire::test(ListImports::class)->assertCountTableRecords(0);
    $this->get(ImportResource::getUrl('controle', ['record' => $import]))->assertNotFound();
});
