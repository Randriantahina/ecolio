<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Absences\Application\Services\ExportAbsencesService;
use Modules\Absences\Domain\Models\Absence;
use Modules\Absences\Filament\Resources\AbsenceResource;
use Modules\Absences\Filament\Resources\Pages\ListAbsences;
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

    $creer = fn (string $nom, $classe) => app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        $nom, null, null, null, null, null, $classe->uuid, 'nouvelle', false, false,
    ))['eleve'];
    $this->rakoto = $creer('Rakoto', $this->classeA);
    $this->hery = $creer('Hery', $this->classeB);

    $saisir = fn ($classe, string $date, array $absents) => app(AbsenceService::class)->saisir($this->directeur, $classe->uuid, $date, 'journee', $absents);
    $saisir($this->classeA, '2026-10-12', [['eleve_uuid' => $this->rakoto->uuid, 'justifiee' => false]]);
    $saisir($this->classeB, '2026-10-05', [['eleve_uuid' => $this->hery->uuid, 'justifiee' => true, 'motif' => 'Malade']]);
});

it('lists the absences of the school, newest first, with pupil and class', function () {
    Livewire::test(ListAbsences::class)
        ->assertCanSeeTableRecords(Absence::orderByDesc('date')->get(), inOrder: true)
        ->assertSee('Rakoto')->assertSee($this->classeA->nom);
});

it('filters by class, by justification and by date range', function () {
    Livewire::test(ListAbsences::class)
        ->filterTable('classe', $this->classeA->id)
        ->assertCountTableRecords(1)->assertSee('Rakoto')->assertDontSee('Hery')
        ->resetTableFilters()
        ->filterTable('justifiee', true)->assertCountTableRecords(1)->assertSee('Hery');

    Livewire::test(ListAbsences::class)
        ->filterTable('periode_dates', ['du' => '2026-10-10', 'au' => '2026-10-12'])
        ->assertCanSeeTableRecords(Absence::where('eleve_id', $this->rakoto->id)->get())
        ->assertCanNotSeeTableRecords(Absence::where('eleve_id', $this->hery->id)->get());
});

it('justifies an absence with a reason', function () {
    $absence = Absence::where('eleve_id', $this->rakoto->id)->first();

    Livewire::test(ListAbsences::class)->callTableAction('justifier', $absence, ['motif' => 'Rendez-vous médical'])->assertNotified();

    expect($absence->fresh()->justifiee)->toBeTrue()->and($absence->fresh()->motif)->toBe('Rendez-vous médical');
});

it('removes an absence entered by mistake (RG-P5)', function () {
    $absence = Absence::where('eleve_id', $this->rakoto->id)->first();

    Livewire::test(ListAbsences::class)->callTableAction('retirer', $absence)->assertNotified();

    expect(Absence::count())->toBe(1);
});

it('offers a shortcut to the roll-call page', function () {
    Livewire::test(ListAbsences::class)->assertActionExists('appel');
});

it('is open to enseignants and closed to parents', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);
    expect(AbsenceResource::canViewAny())->toBeTrue();

    dansLePanelEcole(utilisateurAvecRole(Role::Parent, $this->ecole), $this->ecole);
    expect(AbsenceResource::canViewAny())->toBeFalse();
});

it('never lists the absences of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    dansLePanelEcole(utilisateurAvecRole(Role::Directeur, $autre), $autre);

    Livewire::test(ListAbsences::class)->assertCountTableRecords(0);
});

it('cannot justify an absence of another school', function () {
    $absence = Absence::withoutGlobalScopes()->first();
    $autre = Ecole::factory()->create();
    $dir = utilisateurAvecRole(Role::Directeur, $autre);

    expect(fn () => app(AbsenceService::class)->justifier($dir, $absence->id, 'x'))
        ->toThrow(ModelNotFoundException::class);
});

it('exports the filtered absences with pupil, class, period and justification', function () {
    $lignes = app(ExportAbsencesService::class)->tableau(Absence::query()->orderBy('date')->get());

    expect($lignes[0])->toBe(['Date', 'Matricule', 'Élève', 'Classe', 'Période', 'Justifiée', 'Motif'])
        ->and($lignes[1][0])->toBe('05/10/2026')->and($lignes[1][2])->toBe('Hery')->and($lignes[1][5])->toBe('Oui')->and($lignes[1][6])->toBe('Malade')
        ->and($lignes[2][5])->toBe('Non');

    Livewire::test(ListAbsences::class)->callAction('exporter', ['format' => 'xlsx'])->assertFileDownloaded();
});
