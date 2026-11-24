<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Dynamique\Application\Services\VueService;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Domain\Models\VueTableau;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\Pages\ListEleves;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    dansLePanelEcole($this->directeur, $this->ecole);
    $this->rakoto = Eleve::factory()->create(['nom' => 'Rakoto']);
    $this->rabe = Eleve::factory()->create(['nom' => 'Rabe']);
});

it('saves the current search and sort as a named view', function () {
    Livewire::test(ListEleves::class)
        ->searchTable('Rakoto')->sortTable('nom', 'desc')
        ->callAction('enregistrerVue', ['nom' => 'Les Rakoto'])->assertNotified();

    $vue = VueTableau::firstOrFail();
    expect($vue->nom)->toBe('Les Rakoto')->and($vue->etat['recherche'])->toBe('Rakoto')->and($vue->etat['tri'])->toBe('nom:desc');
});

it('applies a saved view: search, sort and filters come back', function () {
    $vue = app(VueService::class)->enregistrer($this->directeur, EntiteVue::Eleve, 'Les Rakoto', [
        'filtres' => ['statut' => ['value' => 'actif']], 'recherche' => 'Rakoto', 'tri' => 'nom:asc', 'colonnes' => [],
    ]);

    Livewire::test(ListEleves::class)
        ->callAction('chargerVue', ['vue' => $vue->uuid])
        ->assertSet('tableSearch', 'Rakoto')->assertSet('tableSort', 'nom:asc')
        ->assertCanSeeTableRecords([$this->rakoto])->assertCanNotSeeTableRecords([$this->rabe]);
});

it('opens on the user\'s default view', function () {
    app(VueService::class)->enregistrer($this->directeur, EntiteVue::Eleve, 'Par défaut', [
        'filtres' => [], 'recherche' => 'Rabe', 'tri' => null, 'colonnes' => [],
    ], parDefaut: true);

    Livewire::test(ListEleves::class)->assertSet('tableSearch', 'Rabe')->assertCanSeeTableRecords([$this->rabe])->assertCanNotSeeTableRecords([$this->rakoto]);
});

it('lets the directeur share a view and shows it to the secretaire', function () {
    Livewire::test(ListEleves::class)->searchTable('Rakoto')
        ->callAction('enregistrerVue', ['nom' => 'Partagée', 'est_partagee' => true]);

    $secretaire = utilisateurAvecRole(Role::Secretaire, $this->ecole);
    dansLePanelEcole($secretaire, $this->ecole);
    $vue = VueTableau::firstOrFail();

    Livewire::test(ListEleves::class)->callAction('chargerVue', ['vue' => $vue->uuid])->assertSet('tableSearch', 'Rakoto');
});

it('deletes a view', function () {
    $vue = app(VueService::class)->enregistrer($this->directeur, EntiteVue::Eleve, 'À jeter', ['filtres' => [], 'recherche' => null, 'tri' => null, 'colonnes' => []]);

    Livewire::test(ListEleves::class)->callAction('supprimerVue', ['vue' => $vue->uuid])->assertNotified();

    expect(VueTableau::count())->toBe(0);
});
