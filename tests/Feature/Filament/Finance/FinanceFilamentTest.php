<?php

declare(strict_types=1);

use App\Application\Eleves\DTOs\CreateEleveDto;
use App\Application\Eleves\Services\EleveService;
use App\Application\Finance\Services\FraisService;
use App\Domain\Finance\Enums\StatutEcheance;
use App\Domain\Finance\Enums\StatutPaiement;
use App\Domain\Finance\Models\Echeance;
use App\Domain\Finance\Models\Paiement;
use App\Domain\Finance\Models\Tarif;
use App\Domain\Finance\Models\TypeFrais;
use App\Domain\Identity\Enums\Role;
use App\Domain\Platform\Models\Ecole;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Tenancy\TenantContext;
use App\Filament\Finance\Pages\RegistreEcolage;
use App\Filament\Finance\Resources\Pages\CreateTarif;
use App\Filament\Finance\Resources\Pages\CreateTypeFrais;
use App\Filament\Finance\Resources\Pages\EditTarif;
use App\Filament\Finance\Resources\Pages\ListTarifs;
use App\Filament\Finance\Resources\TarifResource;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-12');
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    [$this->classeA, $this->classeB] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $frais = app(FraisService::class);
    $this->type = $frais->creerType($this->directeur, ['nom' => 'Écolage', 'periodicite' => 'mensuel']);
    $this->tarif = $frais->creerTarif($this->directeur, ['type_frais_uuid' => $this->type->uuid, 'montant_ar' => 50000, 'classe_uuid' => $this->classeA->uuid]);

    $this->eleve = app(EleveService::class)->creer($this->directeur, new CreateEleveDto(
        'Rakoto', 'Jean', null, null, null, null, $this->classeA->uuid, 'nouvelle', false, false,
    ))['eleve'];
    // October: the month the frozen clock points at, hence the register's default.
    $this->echeance = Echeance::where('periode', '2026-10')->firstOrFail();
});

it('creates a fee type and edits it', function () {
    Livewire::test(CreateTypeFrais::class)->fillForm(['nom' => 'Inscription', 'periodicite' => 'unique'])
        ->call('create')->assertHasNoFormErrors();

    expect(TypeFrais::where('nom', 'Inscription')->value('periodicite')->value)->toBe('unique');
});

it('creates a tariff for a level and issues the instalments of already enrolled pupils', function () {
    $niveau = Niveau::firstOrFail();
    $cantineLike = app(FraisService::class)->creerType($this->directeur, ['nom' => 'Examens', 'periodicite' => 'unique']);

    Livewire::test(CreateTarif::class)
        ->fillForm(['type_frais_uuid' => $cantineLike->uuid, 'montant_ar' => 15000, 'niveau_uuid' => $niveau->uuid])
        ->call('create')->assertHasNoFormErrors();

    expect(Echeance::where('libelle', 'like', 'Examens%')->count())->toBe(1);
});

it('edits only the amount of a tariff and deletes it', function () {
    Livewire::test(EditTarif::class, ['record' => $this->tarif->uuid])->fillForm(['montant_ar' => 60000])->call('save');
    expect($this->tarif->refresh()->montant_ar)->toBe(60000);

    Livewire::test(ListTarifs::class)->callTableAction(DeleteAction::class, $this->tarif);
    expect(Tarif::where('uuid', $this->tarif->uuid)->exists())->toBeFalse();
});

it('lists the tuition register of a class and month with the amounts', function () {
    Livewire::test(RegistreEcolage::class)
        ->assertSee('Rakoto')->assertSee('50 000 Ar')->assertSee('À payer');
});

it('searches the register by pupil', function () {
    app(EleveService::class)->creer($this->directeur, new CreateEleveDto('Rabe', null, null, null, null, null, $this->classeA->uuid, 'nouvelle', false, false));

    Livewire::test(RegistreEcolage::class)->searchTable('rabe')->assertSee('Rabe')->assertDontSee('Rakoto');
});

it('collects a payment in cash and issues a numbered receipt', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 50000, 'mode' => 'especes', 'date_paiement' => '2026-10-12'])
        ->assertNotified();

    $paiement = Paiement::firstOrFail();
    expect($paiement->total_ar)->toBe(50000)->and($paiement->recu)->not->toBeNull()
        ->and($this->echeance->refresh()->statut)->toBe(StatutEcheance::Paye);
    Livewire::test(RegistreEcolage::class)->assertSee('Payé');
});

it('accepts a partial payment and keeps the balance due (RG-P2)', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 20000, 'mode' => 'especes', 'date_paiement' => '2026-10-12']);

    expect($this->echeance->refresh()->statut)->toBe(StatutEcheance::Partiel);
    Livewire::test(RegistreEcolage::class)->assertSee('30 000 Ar');
});

it('refuses a payment above the remaining balance and creates nothing', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 60000, 'mode' => 'especes', 'date_paiement' => '2026-10-12'])
        ->assertNotified('Paiement refusé');

    expect(Paiement::count())->toBe(0);
});

it('requires a transaction reference for mobile money', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 50000, 'mode' => 'mvola', 'date_paiement' => '2026-10-12'])
        ->assertHasTableActionErrors(['reference_externe' => 'required']);

    expect(Paiement::count())->toBe(0);
});

it('downloads the receipt as a PDF', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 50000, 'mode' => 'especes', 'date_paiement' => '2026-10-12']);

    // A fresh component = the page after the reload that follows a real payment.
    Livewire::test(RegistreEcolage::class)->callTableAction('recu', $this->echeance->uuid)->assertFileDownloaded();
});

it('voids a payment with a mandatory reason, keeping the receipt number (RG-P4)', function () {
    Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 50000, 'mode' => 'especes', 'date_paiement' => '2026-10-12']);

    Livewire::test(RegistreEcolage::class)
        ->callTableAction('annuler', $this->echeance->uuid, ['motif' => ''])->assertHasTableActionErrors(['motif' => 'required']);
    expect(Paiement::firstOrFail()->statut)->toBe(StatutPaiement::Valide);

    Livewire::test(RegistreEcolage::class)->callTableAction('annuler', $this->echeance->uuid, ['motif' => 'Erreur de saisie'])->assertNotified('Paiement annulé');

    $paiement = Paiement::firstOrFail();
    expect($paiement->statut)->toBe(StatutPaiement::Annule)->and($paiement->recu)->not->toBeNull()
        ->and($this->echeance->refresh()->statut)->toBe(StatutEcheance::Du);
});

it('hides voiding from the secretaire but lets them collect (RG-P7)', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Secretaire, $this->ecole), $this->ecole);

    $page = Livewire::test(RegistreEcolage::class)
        ->callTableAction('encaisser', $this->echeance->uuid, ['montant_ar' => 50000, 'mode' => 'especes', 'date_paiement' => '2026-10-12']);
    $page->assertTableActionHidden('annuler', $this->echeance->uuid);
    expect(Paiement::count())->toBe(1);
});

it('keeps the tuition register and prices away from enseignants and parents', function () {
    foreach ([Role::Enseignant, Role::Parent] as $role) {
        dansLePanelEcole(utilisateurAvecRole($role, $this->ecole), $this->ecole);
        expect(RegistreEcolage::canAccess())->toBeFalse()->and(TarifResource::canViewAny())->toBeFalse();
    }
});

it('only lets the directeur set prices', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Secretaire, $this->ecole), $this->ecole);

    expect(TarifResource::canViewAny())->toBeTrue()->and(TarifResource::canCreate())->toBeFalse();
});

it('never shows the tariffs or the payments of another school (tenant isolation)', function () {
    $etranger = app(TenantContext::class)->runAs($this->autre->id, function () {
        $directeur = utilisateurAvecRole(Role::Directeur, $this->autre);
        ecoleAvecClasses($this->autre, $directeur);

        return app(FraisService::class)->creerTarif($directeur, [
            'type_frais_uuid' => app(FraisService::class)->creerType($directeur, ['nom' => 'Secret', 'periodicite' => 'unique'])->uuid,
            'montant_ar' => 1,
        ]);
    });
    dansLePanelEcole($this->directeur, $this->ecole);

    Livewire::test(ListTarifs::class)->assertCanNotSeeTableRecords([$etranger]);
    Livewire::test(EditTarif::class, ['record' => $etranger->uuid])->assertNotFound();
});
