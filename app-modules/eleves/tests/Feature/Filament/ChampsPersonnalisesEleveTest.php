<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\Pages\CreateEleve;
use Modules\Eleves\Filament\Resources\Pages\EditEleve;
use Modules\Eleves\Filament\Resources\Pages\ListEleves;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    [$this->classe] = ecoleAvecClasses($this->ecole, $this->directeur);
    dansLePanelEcole($this->directeur, $this->ecole);

    $champs = app(ChampService::class);
    $this->quartier = $champs->creer($this->directeur, EntiteChamp::Eleve, 'Quartier', TypeChamp::Texte, null, true);
    $champs->creer($this->directeur, EntiteChamp::Eleve, 'Boursier', TypeChamp::Booleen);
    $champs->creer($this->directeur, EntiteChamp::Eleve, 'Religion', TypeChamp::Liste, ['Catholique', 'Protestant']);
});

it('stores custom field values entered in the pupil form', function () {
    Livewire::test(CreateEleve::class)
        ->fillForm(['nom' => 'Rakoto', 'classe_uuid' => $this->classe->uuid, 'champs_perso' => ['quartier' => 'Ambohitsoa', 'boursier' => true, 'religion' => 'Protestant']])
        ->call('create')->assertHasNoFormErrors();

    expect(Eleve::first()->champs_perso)->toBe(['quartier' => 'Ambohitsoa', 'boursier' => true, 'religion' => 'Protestant']);
});

it('refuses a pupil without the mandatory custom field, with the error on that field (RG-D6)', function () {
    Livewire::test(CreateEleve::class)
        ->fillForm(['nom' => 'Rakoto', 'classe_uuid' => $this->classe->uuid])
        ->call('create')->assertHasFormErrors(['champs_perso.quartier']);

    expect(Eleve::count())->toBe(0);
});

it('shows and edits the stored values of an existing pupil', function () {
    $eleve = Eleve::factory()->create(['champs_perso' => ['quartier' => 'Analakely']]);

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])
        ->assertFormSet(['champs_perso.quartier' => 'Analakely'])
        ->fillForm(['champs_perso' => ['quartier' => 'Ivato']])->call('save')->assertHasNoFormErrors();

    expect($eleve->fresh()->champs_perso['quartier'])->toBe('Ivato');
});

it('keeps the values of a deactivated field stored (data kept 90 days)', function () {
    $eleve = Eleve::factory()->create(['champs_perso' => ['quartier' => 'Analakely', 'boursier' => true]]);
    app(ChampService::class)->desactiver($this->directeur, app(ChampService::class)->lister($this->directeur, EntiteChamp::Eleve)->firstWhere('cle', 'boursier')->uuid);

    Livewire::test(EditEleve::class, ['record' => $eleve->uuid])->fillForm(['nom' => 'Nouveau'])->call('save');

    expect($eleve->fresh()->champs_perso)->toEqual(['quartier' => 'Analakely', 'boursier' => true]);
});

it('adds a sortable, searchable column per active custom field to the register (RG-D5)', function () {
    $a = Eleve::factory()->create(['nom' => 'Aaa', 'champs_perso' => ['quartier' => 'Zoma']]);
    $b = Eleve::factory()->create(['nom' => 'Bbb', 'champs_perso' => ['quartier' => 'Andohalo']]);

    Livewire::test(ListEleves::class)
        ->assertTableColumnExists('champs_perso_quartier')
        ->sortTable('champs_perso_quartier')->assertCanSeeTableRecords([$b, $a], inOrder: true)
        ->searchTable('Zoma')->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b]);
});

it('filters the register by a list field', function () {
    $a = Eleve::factory()->create(['champs_perso' => ['quartier' => 'X', 'religion' => 'Catholique']]);
    $b = Eleve::factory()->create(['champs_perso' => ['quartier' => 'X', 'religion' => 'Protestant']]);

    Livewire::test(ListEleves::class)->filterTable('champs_perso_religion', 'Protestant')
        ->assertCanSeeTableRecords([$b])->assertCanNotSeeTableRecords([$a]);
});

it('only offers the custom fields of the school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    $dirAutre = utilisateurAvecRole(Role::Administration, $autre);
    dansLePanelEcole($dirAutre, $autre);
    app(ChampService::class)->creer($dirAutre, EntiteChamp::Eleve, 'Secret', TypeChamp::Texte);
    dansLePanelEcole($this->directeur, $this->ecole);

    Livewire::test(ListEleves::class)->assertTableColumnDoesNotExist('champs_perso_secret');
});
