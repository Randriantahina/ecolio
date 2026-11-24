<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Absences\Domain\Models\Absence;
use Modules\Absences\Filament\Pages\AppelDuJour;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-12');
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classeA, $this->classeB] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $this->inscrire = fn (string $nom, string $classe) => app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        $nom, null, null, null, null, null, $classe, 'nouvelle', false, false,
    ))['eleve'];
    $this->rakoto = ($this->inscrire)('Rakoto', $this->classeA->uuid);
    $this->rabe = ($this->inscrire)('Rabe', $this->classeA->uuid);
    $this->autreClasse = ($this->inscrire)('Hery', $this->classeB->uuid);
});

it('opens on the first class with today and nobody absent', function () {
    Livewire::test(AppelDuJour::class)
        ->assertSet('data.classe_uuid', $this->classeA->uuid)
        ->assertSet('data.date', '2026-10-12')
        ->assertSet('data.periode', 'journee')
        ->assertSet('data.absents', []);
});

it('records the absentees and marks the justified ones', function () {
    Livewire::test(AppelDuJour::class)
        ->fillForm(['absents' => [$this->rakoto->uuid, $this->rabe->uuid], 'justifiees' => [$this->rabe->uuid]])
        ->call('enregistrer')->assertNotified('Appel enregistré');

    expect(Absence::count())->toBe(2)
        ->and(Absence::where('eleve_id', $this->rabe->id)->value('justifiee'))->toBeTrue()
        ->and(Absence::where('eleve_id', $this->rakoto->id)->value('justifiee'))->toBeFalse();
});

it('reloads what was recorded and lets the user untick an absentee (RG-P5)', function () {
    Livewire::test(AppelDuJour::class)->fillForm(['absents' => [$this->rakoto->uuid, $this->rabe->uuid]])->call('enregistrer');

    Livewire::test(AppelDuJour::class)
        ->assertSet('data.absents', fn (array $a): bool => count($a) === 2)
        ->fillForm(['absents' => [$this->rakoto->uuid]])->call('enregistrer');

    expect(Absence::count())->toBe(1);
});

it('empties the list with the "all present" action', function () {
    Livewire::test(AppelDuJour::class)
        ->fillForm(['absents' => [$this->rakoto->uuid]])
        ->callAction('toutPresents')->assertSet('data.absents', []);
});

it('lists only the pupils of the chosen class', function () {
    $page = Livewire::test(AppelDuJour::class);
    expect($page->instance()->form->getComponent('absents')->getOptions())
        ->toHaveCount(2)->toHaveKeys([$this->rakoto->uuid, $this->rabe->uuid])->not->toHaveKey($this->autreClasse->uuid);

    $page->fillForm(['classe_uuid' => $this->classeB->uuid]);
    expect($page->instance()->form->getComponent('absents')->getOptions())->toHaveKey($this->autreClasse->uuid)->toHaveCount(1);
});

it('refuses a future date', function () {
    Livewire::test(AppelDuJour::class)->fillForm(['date' => '2026-10-20'])->call('enregistrer')->assertHasFormErrors(['date']);
});

it('is open to enseignants and closed to parents', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);
    expect(AppelDuJour::canAccess())->toBeTrue();

    dansLePanelEcole(utilisateurAvecRole(Role::Parent, $this->ecole), $this->ecole);
    expect(AppelDuJour::canAccess())->toBeFalse();
});

it('never lists the classes of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    ecoleAvecClasses($autre, utilisateurAvecRole(Role::Directeur, $autre));
    dansLePanelEcole($this->directeur, $this->ecole);

    expect(Livewire::test(AppelDuJour::class)->instance()->form->getComponent('classe_uuid')->getOptions())->toHaveCount(2);
});

it('is attached to the school panel by its module and reachable through a real request', function () {
    expect(Filament\Facades\Filament::getPanel('ecole')->getPlugins())->toHaveKey('absences')
        ->and(Filament\Facades\Filament::getPanel('ecole')->getPages())->toContain(AppelDuJour::class);

    $this->get("/ecole/{$this->ecole->slug}/appel-du-jour")->assertOk();
});
