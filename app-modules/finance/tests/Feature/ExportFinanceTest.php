<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Finance\Application\DTOs\EncaisserDto;
use Modules\Finance\Application\Services\ExportFinanceService;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Application\Services\PaiementService;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Finance\Filament\Pages\RegistreEcolage;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-12');
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classe] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $frais = app(FraisService::class);
    $type = $frais->creerType($this->directeur, ['nom' => 'Inscription', 'periodicite' => 'unique']);
    $frais->creerTarif($this->directeur, ['type_frais_uuid' => $type->uuid, 'montant_ar' => 50000]);
    $this->eleve = app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        'Rakoto', 'Jean', null, null, null, null, $this->classe->uuid, 'nouvelle', false, false,
    ))['eleve'];
    $this->echeance = Echeance::firstOrFail();
});

it('lists unpaid instalments of the whole school with the balance, class and phone-free pupil identity', function () {
    app(PaiementService::class)->encaisser($this->directeur, new EncaisserDto(
        $this->eleve->uuid, [['echeance_uuid' => $this->echeance->uuid, 'montant_ar' => 20000]], 'especes', '2026-10-12', null, null,
    ));

    [$entetes, $lignes] = app(ExportFinanceService::class)->impayes($this->directeur);

    expect($entetes)->toBe(['Matricule', 'Élève', 'Classe', 'Frais', 'Montant', 'Payé', 'Reste', 'Statut'])
        ->and($lignes)->toHaveCount(1)
        ->and($lignes[0])->toBe([$this->eleve->matricule, 'Rakoto Jean', $this->classe->nom, 'Inscription', 50000, 20000, 30000, 'Partiel']);
});

it('leaves fully paid instalments out of the unpaid list', function () {
    app(PaiementService::class)->encaisser($this->directeur, new EncaisserDto(
        $this->eleve->uuid, [['echeance_uuid' => $this->echeance->uuid, 'montant_ar' => 50000]], 'especes', '2026-10-12', null, null,
    ));

    expect(app(ExportFinanceService::class)->impayes($this->directeur)[1])->toBe([]);
});

it('downloads the register view and the unpaid list from the register page', function () {
    Livewire::test(RegistreEcolage::class)->set('activeTab', 'inscriptions')
        ->callAction('exporter', ['format' => 'xlsx'])->assertFileDownloaded();
    Livewire::test(RegistreEcolage::class)->callAction('exporterImpayes', ['format' => 'csv'])->assertFileDownloaded();
});

it('never exports the instalments of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();

    expect(app(ExportFinanceService::class)->impayes(utilisateurAvecRole(Role::Directeur, $autre))[1])->toBe([]);
});
