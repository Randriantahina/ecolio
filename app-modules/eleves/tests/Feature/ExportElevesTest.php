<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Application\Services\ExportElevesService;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\Pages\ListEleves;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    dansLePanelEcole($this->directeur, $this->ecole);
    app(ChampService::class)->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte);
    app(ChampService::class)->creer($this->directeur, EntiteChamp::Eleve, 'Boursier', TypeChamp::Booleen);
    $this->rakoto = Eleve::factory()->create(['nom' => 'Rakoto', 'sexe' => 'M', 'date_naissance' => '2014-03-02', 'champs_perso' => ['quartier' => 'Ivato', 'boursier' => true]]);
});

it('maps pupils to rows: core columns, then every active custom field (RG-D5)', function () {
    [$entetes, $lignes] = app(ExportElevesService::class)->tableau($this->directeur, Eleve::query()->get());

    expect($entetes)->toBe(['Matricule', 'Nom', 'Prénoms', 'Sexe', 'Date de naissance', 'Classe', 'Statut', 'Quartier', 'Boursier'])
        ->and($lignes[0][1])->toBe('Rakoto')->and($lignes[0][4])->toBe('02/03/2014')
        ->and($lignes[0][7])->toBe('Ivato')->and($lignes[0][8])->toBe('Oui');
});

it('downloads the current view of the register as xlsx', function () {
    Eleve::factory()->create(['nom' => 'Rabe']);

    Livewire::test(ListEleves::class)->searchTable('Rakoto')
        ->callAction('exporter', ['format' => 'xlsx'])->assertFileDownloaded();
});

it('downloads csv too', function () {
    Livewire::test(ListEleves::class)->callAction('exporter', ['format' => 'csv'])->assertFileDownloaded();
});

it('exports only the current view, not the whole school', function () {
    Eleve::factory()->create(['nom' => 'Rabe']);

    $page = Livewire::test(ListEleves::class)->searchTable('Rakoto');
    $lignes = (fn () => app(ExportElevesService::class)->tableau(auth()->user(), $this->getFilteredSortedTableQuery()->get())[1])->call($page->instance());

    expect($lignes)->toHaveCount(1)->and($lignes[0][1])->toBe('Rakoto');
});

it('never exports the pupils of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    dansLePanelEcole(utilisateurAvecRole(Role::Administration, $autre), $autre);
    Eleve::factory()->create(['nom' => 'Secret']);
    dansLePanelEcole($this->directeur, $this->ecole);

    $noms = collect(app(ExportElevesService::class)->tableau($this->directeur, Eleve::query()->get())[1])->pluck(1);
    expect($noms->all())->toBe(['Rakoto']);
});
