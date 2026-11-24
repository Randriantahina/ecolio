<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\EleveResource;
use Modules\Eleves\Filament\Resources\Pages\CreateEleve;
use Modules\Eleves\Filament\Resources\Pages\EditEleve;
use Modules\Eleves\Filament\Resources\Pages\ListEleves;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    [$this->classeA, $this->classeB] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $this->creerEleve = function (array $data = []) {
        Livewire::test(CreateEleve::class)
            ->fillForm($data + ['nom' => 'Rakoto', 'prenoms' => 'Jean', 'classe_uuid' => $this->classeA->uuid])
            ->call('create');

        return Eleve::query()->where('nom', $data['nom'] ?? 'Rakoto')->first();
    };
});

it('lists the pupils of the school with their class', function () {
    $eleve = ($this->creerEleve)();

    Livewire::test(ListEleves::class)
        ->assertCanSeeTableRecords([$eleve])
        ->assertTableColumnStateSet('inscriptionActive.classe.nom', '6e A', $eleve);
});

it('searches by name and by matricule', function () {
    $rakoto = ($this->creerEleve)();
    $rabe = ($this->creerEleve)(['nom' => 'Rabe']);

    Livewire::test(ListEleves::class)->searchTable('rabe')
        ->assertCanSeeTableRecords([$rabe])->assertCanNotSeeTableRecords([$rakoto]);
    Livewire::test(ListEleves::class)->searchTable($rakoto->matricule)
        ->assertCanSeeTableRecords([$rakoto])->assertCanNotSeeTableRecords([$rabe]);
});

it('filters by class', function () {
    $a = ($this->creerEleve)();
    $b = ($this->creerEleve)(['nom' => 'Rabe', 'classe_uuid' => $this->classeB->uuid]);

    Livewire::test(ListEleves::class)->filterTable('classe', $this->classeB->uuid)
        ->assertCanSeeTableRecords([$b])->assertCanNotSeeTableRecords([$a]);
});

it('creates a pupil with a generated matricule, enrolled in the chosen class', function () {
    $eleve = ($this->creerEleve)();

    expect($eleve)->not->toBeNull()
        ->and($eleve->matricule)->not->toBeEmpty()
        ->and($eleve->inscriptionActive->classe->nom)->toBe('6e A')
        ->and($eleve->ecole_id)->toBe($this->ecole->id);
});

it('requires a name', function () {
    Livewire::test(CreateEleve::class)->fillForm(['nom' => ''])->call('create')->assertHasFormErrors(['nom' => 'required']);
});

it('warns about a likely duplicate and creates it only once confirmed (RG-E4)', function () {
    $base = ['nom' => 'Rakoto', 'prenoms' => 'Jean', 'date_naissance' => '2014-05-02'];
    ($this->creerEleve)($base);

    $page = Livewire::test(CreateEleve::class)->fillForm($base)->call('create');
    $page->assertNotified('Doublon probable')->assertSet('doublonDetecte', true);
    expect(Eleve::count())->toBe(1);

    $page->fillForm(['confirmer_doublon' => true])->call('create')->assertHasNoFormErrors();
    expect(Eleve::count())->toBe(2);
});

it('warns when the class goes over its capacity (RG-I2)', function () {
    ($this->creerEleve)(['nom' => 'Un']);

    Livewire::test(CreateEleve::class)
        ->fillForm(['nom' => 'Deux', 'classe_uuid' => $this->classeA->uuid])
        ->call('create')->assertNotified('Effectif maximal dépassé');
    expect(Eleve::count())->toBe(2);
});

it('edits the identity of a pupil', function () {
    $eleve = ($this->creerEleve)();

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])
        ->fillForm(['nom' => 'Rakotoson', 'lieu_naissance' => 'Antsirabe'])
        ->call('save')->assertHasNoFormErrors();

    expect($eleve->refresh()->nom)->toBe('Rakotoson')->and($eleve->lieu_naissance)->toBe('Antsirabe');
});

it('lets only the directeur correct the matricule (RG-E1)', function () {
    $eleve = ($this->creerEleve)();

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])->fillForm(['matricule' => 'X-1'])->call('save');
    expect($eleve->refresh()->matricule)->toBe('X-1');

});

it('moves a pupil to another class', function () {
    $eleve = ($this->creerEleve)();

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])
        ->callAction('changerClasse', ['classe_uuid' => $this->classeB->uuid])->assertNotified('Classe modifiée');

    expect($eleve->fresh('inscriptionActive.classe')->inscriptionActive->classe->nom)->toBe('6e B');
});

it('records a departure and then hides the departure actions (RG-P5)', function () {
    $eleve = ($this->creerEleve)();

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])
        ->callAction('depart', ['statut' => 'transfere', 'date_sortie' => '2026-12-15', 'motif_sortie' => 'Déménagement'])
        ->assertNotified('Départ enregistré');

    expect($eleve->refresh()->statut)->toBe(StatutEleve::Transfere);
    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])->assertActionHidden('depart')->assertActionHidden('changerClasse');
});

it('keeps enseignants and role-less users out of the registry', function () {
    $eleve = ($this->creerEleve)();

    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);
    expect(EleveResource::canViewAny())->toBeFalse()->and(EleveResource::canCreate())->toBeFalse()
        ->and(EleveResource::canEdit($eleve))->toBeFalse();

    dansLePanelEcole(utilisateurSansRole($this->ecole), $this->ecole);
    expect(EleveResource::canViewAny())->toBeFalse();
});

it('never shows a pupil of another school (tenant isolation)', function () {
    $etranger = app(TenantContext::class)->runAs($this->autre->id, fn () => Eleve::create(['nom' => 'Secret', 'matricule' => 'X-9']));
    dansLePanelEcole($this->directeur, $this->ecole);

    Livewire::test(ListEleves::class)->removeTableFilter('statut')->assertCanNotSeeTableRecords([$etranger]);
    Livewire::test(EditEleve::class, ['record' => $etranger->uuid])->assertNotFound();
});

it('renders the list page through a real HTTP request, with the school context bound by the middleware', function () {
    ($this->creerEleve)();

    $this->get("/ecole/{$this->ecole->slug}/eleves")->assertOk()->assertSee('Rakoto');
});
