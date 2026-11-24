<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Filament\Widgets\EffectifsStats;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Widgets\FinanceStats;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

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
