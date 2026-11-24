<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;
use Modules\Dynamique\Filament\Resources\ChampPersonnaliseResource;
use Modules\Dynamique\Filament\Resources\Pages\CreateChampPersonnalise;
use Modules\Dynamique\Filament\Resources\Pages\EditChampPersonnalise;
use Modules\Dynamique\Filament\Resources\Pages\ListChampsPersonnalises;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    dansLePanelEcole($this->directeur, $this->ecole);
    $this->quartier = app(ChampService::class)->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);
});

it('is attached to the school panel by its module', function () {
    expect(Filament\Facades\Filament::getPanel('ecole')->getPlugins())->toHaveKey('dynamique');
});

it('lists the active fields with their technical key', function () {
    Livewire::test(ListChampsPersonnalises::class)->assertCanSeeTableRecords([$this->quartier])->assertSee('quartier');
});

it('hides deactivated fields until asked, and can reactivate them', function () {
    app(ChampService::class)->desactiver($this->directeur, $this->quartier->uuid);

    Livewire::test(ListChampsPersonnalises::class)->assertCanNotSeeTableRecords([$this->quartier])
        ->filterTable('actif', false)->assertCanSeeTableRecords([$this->quartier])
        ->callTableAction('reactiver', $this->quartier)->assertNotified();

    expect($this->quartier->fresh()->actif)->toBeTrue();
});

it('deactivates instead of deleting', function () {
    Livewire::test(ListChampsPersonnalises::class)->callTableAction('desactiver', $this->quartier)->assertNotified();

    expect($this->quartier->fresh()->actif)->toBeFalse()->and(ChampPersonnalise::count())->toBe(1);
});

it('creates a list field with its choices', function () {
    Livewire::test(CreateChampPersonnalise::class)
        ->fillForm(['entite' => 'eleve', 'libelle' => 'Religion', 'type' => 'liste', 'options' => ['Catholique', 'Protestant'], 'obligatoire' => true])
        ->call('create')->assertHasNoFormErrors();

    $champ = ChampPersonnalise::where('cle', 'religion')->firstOrFail();
    expect($champ->options)->toBe(['Catholique', 'Protestant'])->and($champ->obligatoire)->toBeTrue();
});

it('refuses a list field without choices, error on the options', function () {
    Livewire::test(CreateChampPersonnalise::class)
        ->fillForm(['entite' => 'eleve', 'libelle' => 'Religion', 'type' => 'liste'])
        ->call('create')->assertHasFormErrors(['options']);
});

it('renames a field without touching its key and edits the mandatory flag', function () {
    Livewire::test(EditChampPersonnalise::class, ['record' => $this->quartier->uuid])
        ->assertFormSet(['libelle' => 'Quartier', 'type' => 'texte'])
        ->fillForm(['libelle' => 'Quartier de résidence', 'obligatoire' => true])->call('save')->assertHasNoFormErrors();

    expect($this->quartier->fresh())->libelle->toBe('Quartier de résidence')->cle->toBe('quartier')->obligatoire->toBeTrue();
});

it('changes the type when values convert and refuses it otherwise (RG-D3)', function () {
    Livewire::test(EditChampPersonnalise::class, ['record' => $this->quartier->uuid])
        ->fillForm(['type' => 'nombre'])->call('save')->assertHasNoFormErrors();

    expect($this->quartier->fresh()->type)->toBe(TypeChamp::Nombre);
});

it('is manageable by the directeur only; staff just read', function () {
    foreach ([Role::Secretaire, Role::Enseignant] as $role) {
        dansLePanelEcole(utilisateurAvecRole($role, $this->ecole), $this->ecole);
        expect(ChampPersonnaliseResource::canViewAny())->toBeTrue()->and(ChampPersonnaliseResource::canCreate())->toBeFalse();
    }
    dansLePanelEcole(utilisateurAvecRole(Role::Parent, $this->ecole), $this->ecole);
    expect(ChampPersonnaliseResource::canViewAny())->toBeFalse();
});

it('never lists or opens the fields of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    $dirAutre = utilisateurAvecRole(Role::Directeur, $autre);
    dansLePanelEcole($dirAutre, $autre);
    $secret = app(ChampService::class)->creer($dirAutre, EntiteChamp::Eleve, 'Secret', TypeChamp::Texte);
    dansLePanelEcole($this->directeur, $this->ecole);

    Livewire::test(ListChampsPersonnalises::class)->assertCanNotSeeTableRecords([$secret]);
    Livewire::test(EditChampPersonnalise::class, ['record' => $secret->uuid])->assertNotFound();
});
