<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Finance\Application\DTOs\EncaisserDto;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Application\Services\PaiementService;
use Modules\Finance\Application\Services\TableauDeBordFinanceService;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-12');
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classe] = ecoleAvecClasses($this->ecole, $this->directeur);
    app(TenantContext::class)->forEcole($this->ecole->id);

    $frais = app(FraisService::class);
    $type = $frais->creerType($this->directeur, ['nom' => 'Écolage', 'periodicite' => 'unique']);
    $frais->creerTarif($this->directeur, ['type_frais_uuid' => $type->uuid, 'montant_ar' => 50000]);
    $this->eleve = app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        'Rakoto', null, null, null, null, null, $this->classe->uuid, 'nouvelle', false, false,
    ))['eleve'];
    $this->echeance = Echeance::firstOrFail();

    $this->payer = fn (int $montant, string $date = '2026-10-12') => app(PaiementService::class)->encaisser($this->directeur, new EncaisserDto(
        $this->eleve->uuid, [['echeance_uuid' => $this->echeance->uuid, 'montant_ar' => $montant]], 'especes', $date, null, null,
    ))['paiement'];
    $this->tableau = fn () => app(TableauDeBordFinanceService::class)->pour($this->directeur);
});

it('shows everything owed and no cash when nothing was paid', function () {
    expect(($this->tableau)())->toBe(['encaisse_mois_ar' => 0, 'reste_a_payer_ar' => 50000, 'echeances_impayees' => 1]);
});

it('counts a partial payment as cash in and keeps the balance due', function () {
    ($this->payer)(20000);

    expect(($this->tableau)())->toBe(['encaisse_mois_ar' => 20000, 'reste_a_payer_ar' => 30000, 'echeances_impayees' => 1]);
});

it('closes the instalment when fully paid', function () {
    ($this->payer)(50000);

    expect(($this->tableau)())->toBe(['encaisse_mois_ar' => 50000, 'reste_a_payer_ar' => 0, 'echeances_impayees' => 0]);
});

it('gives the money back to the balance when a payment is voided', function () {
    $paiement = ($this->payer)(50000);
    app(PaiementService::class)->annuler($this->directeur, $paiement->uuid, 'Erreur');

    expect(($this->tableau)())->toBe(['encaisse_mois_ar' => 0, 'reste_a_payer_ar' => 50000, 'echeances_impayees' => 1]);
});

it('counts only the payments of the current month as cash in', function () {
    ($this->payer)(10000, '2026-09-30');
    ($this->payer)(20000, '2026-10-02');

    expect(($this->tableau)()['encaisse_mois_ar'])->toBe(20000)->and(($this->tableau)()['reste_a_payer_ar'])->toBe(20000);
});

it('never counts the money of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    $dirAutre = utilisateurAvecRole(Role::Directeur, $autre);

    expect(app(TableauDeBordFinanceService::class)->pour($dirAutre))
        ->toBe(['encaisse_mois_ar' => 0, 'reste_a_payer_ar' => 0, 'echeances_impayees' => 0]);
});
