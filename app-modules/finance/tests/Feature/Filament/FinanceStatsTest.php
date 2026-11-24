<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Absences\Filament\Widgets\AbsencesParJourChart;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Filament\Widgets\EffectifsStats;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Widgets\EcheancesParStatutChart;
use Modules\Finance\Filament\Widgets\EncaisseParMoisChart;
use Modules\Finance\Filament\Widgets\FinanceStats;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

// Chart data providers are protected on purpose: read them the way the widget itself does.
function chartData(string $widget): array
{
    return (fn (): array => $this->getData())->call(new $widget);
}

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classe] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $type = app(FraisService::class)->creerType($this->directeur, ['nom' => 'Écolage', 'periodicite' => 'unique']);
    app(FraisService::class)->creerTarif($this->directeur, ['type_frais_uuid' => $type->uuid, 'montant_ar' => 50000]);
    app(EleveService::class)->creer($this->directeur, new CreateEleveDto('Rakoto', null, null, null, null, null, $this->classe->uuid, 'nouvelle', false, false));
});

it('shows the money figures on the dashboard', function () {
    Livewire::test(FinanceStats::class)->assertSee('Encaissé ce mois')->assertSee('50 000 Ar')->assertSee('1 échéance(s) non soldée(s)');
});

it('shows the headcount on the dashboard', function () {
    Livewire::test(EffectifsStats::class)->assertSee('Élèves actifs')->assertSee('Sans classe');
});

it('hides the money widget from enseignants but keeps the headcount', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);

    expect(FinanceStats::canView())->toBeFalse()->and(EffectifsStats::canView())->toBeTrue();
});

it('registers both widgets on the school panel and serves the dashboard in French', function () {
    expect(Filament\Facades\Filament::getPanel('ecole')->getWidgets())->toContain(FinanceStats::class, EffectifsStats::class);

    $this->get("/ecole/{$this->ecole->slug}")->assertOk()->assertSee('lang="fr"', false);
});

it('feeds the three dashboard charts with the school figures', function () {
    $paiement = chartData(EncaisseParMoisChart::class);
    expect($paiement)->toHaveKeys(['datasets', 'labels']);
    expect($paiement['labels'])->toHaveCount(6);

    $statuts = chartData(EcheancesParStatutChart::class);
    expect($statuts['datasets'][0]['data'])->toBe([1, 0, 0, 0]);

    $absences = chartData(AbsencesParJourChart::class);
    expect($absences['labels'])->toHaveCount(14)->and(array_sum($absences['datasets'][0]['data']))->toBe(0);
});

it('hides the money charts from enseignants', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);

    expect(EncaisseParMoisChart::canView())->toBeFalse()
        ->and(AbsencesParJourChart::canView())->toBeTrue();
});
