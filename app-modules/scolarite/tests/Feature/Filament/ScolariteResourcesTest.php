<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Eleves\Filament\Resources\Pages\CreateEleve;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Scolarite\Domain\Enums\Cycle;
use Modules\Scolarite\Domain\Models\AnneeScolaire;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;
use Modules\Scolarite\Filament\Resources\AnneeScolaireResource;
use Modules\Scolarite\Filament\Resources\Pages\CreateAnneeScolaire;
use Modules\Scolarite\Filament\Resources\Pages\CreateClasse;
use Modules\Scolarite\Filament\Resources\Pages\CreateNiveau;
use Modules\Scolarite\Filament\Resources\Pages\EditAnneeScolaire;
use Modules\Scolarite\Filament\Resources\Pages\EditClasse;
use Modules\Scolarite\Filament\Resources\Pages\EditNiveau;
use Modules\Scolarite\Filament\Resources\Pages\ListAnneesScolaires;
use Modules\Scolarite\Filament\Resources\Pages\ListClasses;
use Modules\Scolarite\Filament\Resources\Pages\ListNiveaux;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    [$this->classeA, $this->classeB] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);
});

it('creates a school year and makes it the current one', function () {
    Livewire::test(CreateAnneeScolaire::class)
        ->fillForm(['libelle' => '2027-2028', 'date_debut' => '2027-09-01', 'date_fin' => '2028-06-30', 'est_courante' => true])
        ->call('create')->assertHasNoFormErrors();

    expect(AnneeScolaire::where('libelle', '2027-2028')->value('est_courante'))->toBeTrue()
        ->and(AnneeScolaire::where('libelle', '2026-2027')->value('est_courante'))->toBeFalse();
});

it('refuses a school year that ends before it starts', function () {
    Livewire::test(CreateAnneeScolaire::class)
        ->fillForm(['libelle' => 'X', 'date_debut' => '2027-09-01', 'date_fin' => '2027-01-01'])
        ->call('create')->assertHasFormErrors(['date_fin']);
});

it('edits a school year and switches the current one from the table', function () {
    $nouvelle = Livewire::test(CreateAnneeScolaire::class)
        ->fillForm(['libelle' => '2027-2028', 'date_debut' => '2027-09-01', 'date_fin' => '2028-06-30'])
        ->call('create')->instance()->getRecord();

    Livewire::test(EditAnneeScolaire::class, ['record' => $nouvelle->uuid])
        ->fillForm(['libelle' => '2027-28'])->call('save')->assertHasNoFormErrors();
    expect($nouvelle->refresh()->libelle)->toBe('2027-28');

    Livewire::test(ListAnneesScolaires::class)->callTableAction('definirCourante', $nouvelle);
    expect($nouvelle->refresh()->est_courante)->toBeTrue();
});

it('creates and edits a level', function () {
    Livewire::test(CreateNiveau::class)->fillForm(['nom' => '5e', 'cycle' => Cycle::College->value, 'ordre' => 5])
        ->call('create')->assertHasNoFormErrors();
    $niveau = Niveau::where('nom', '5e')->firstOrFail();
    expect($niveau->cycle)->toBe(Cycle::College);

    Livewire::test(EditNiveau::class, ['record' => $niveau->uuid])
        ->fillForm(['ordre' => 7])->call('save');
    expect($niveau->refresh()->ordre)->toBe(7);

    Livewire::test(ListNiveaux::class)->assertCanSeeTableRecords([$niveau]);
});

it('creates a class in the current year and edits its capacity', function () {
    $niveau = Niveau::firstOrFail();

    Livewire::test(CreateClasse::class)->fillForm(['nom' => '6e C', 'niveau_uuid' => $niveau->uuid, 'effectif_max' => 25])
        ->call('create')->assertHasNoFormErrors();
    $classe = Classe::where('nom', '6e C')->firstOrFail();
    expect($classe->effectif_max)->toBe(25)->and($classe->anneeScolaire->libelle)->toBe('2026-2027');

    Livewire::test(EditClasse::class, ['record' => $classe->uuid])->fillForm(['effectif_max' => 30])->call('save');
    expect($classe->refresh()->effectif_max)->toBe(30);
    Livewire::test(ListClasses::class)->assertCanSeeTableRecords([$classe]);
});

it('refuses two classes with the same name in one year', function () {
    $niveau = Niveau::firstOrFail();

    Livewire::test(CreateClasse::class)->fillForm(['nom' => '6e A', 'niveau_uuid' => $niveau->uuid])->call('create');

    expect(Classe::where('nom', '6e A')->count())->toBe(1);
});

it('deletes an empty class but refuses one that still holds pupils', function () {
    Livewire::test(CreateEleve::class)->fillForm(['nom' => 'Rakoto', 'classe_uuid' => $this->classeA->uuid])->call('create');

    Livewire::test(ListClasses::class)->callTableAction(DeleteAction::class, $this->classeA);
    expect(Classe::where('uuid', $this->classeA->uuid)->exists())->toBeTrue();

    Livewire::test(ListClasses::class)->callTableAction(DeleteAction::class, $this->classeB);
    expect(Classe::where('uuid', $this->classeB->uuid)->exists())->toBeFalse();
});

it('keeps the school structure to the administration', function () {
    expect(AnneeScolaireResource::canViewAny())->toBeTrue()->and(AnneeScolaireResource::canCreate())->toBeTrue();

    foreach ([utilisateurAvecRole(Role::Enseignant, $this->ecole), utilisateurSansRole($this->ecole)] as $autre) {
        dansLePanelEcole($autre, $this->ecole);
        expect(AnneeScolaireResource::canViewAny())->toBeFalse();
    }
});

it('never shows the structure of another school (tenant isolation)', function () {
    $etrangere = app(TenantContext::class)->runAs($this->autre->id, fn () => Niveau::create(['nom' => 'Terminale']));
    dansLePanelEcole($this->directeur, $this->ecole);

    Livewire::test(ListNiveaux::class)->assertCanNotSeeTableRecords([$etrangere]);
    Livewire::test(EditNiveau::class, ['record' => $etrangere->uuid])->assertNotFound();
});
