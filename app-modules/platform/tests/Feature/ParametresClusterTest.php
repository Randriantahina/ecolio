<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dynamique\Filament\Resources\ChampPersonnaliseResource;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Platform\Filament\Clusters\ParametresCluster;
use Modules\Scolarite\Filament\Resources\AnneeScolaireResource;
use Modules\Scolarite\Filament\Resources\ClasseResource;
use Modules\Scolarite\Filament\Resources\NiveauResource;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    dansLePanelEcole($this->directeur, $this->ecole);
});

it('is attached to the school panel by the platform module', function () {
    expect(Filament::getPanel('ecole')->getPlugins())->toHaveKey('platform');
});

it('groups school structure and custom fields under the Parametres cluster', function () {
    foreach ([AnneeScolaireResource::class, NiveauResource::class, ClasseResource::class, ChampPersonnaliseResource::class] as $ressource) {
        expect($ressource::getCluster())->toBe(ParametresCluster::class);
    }
});

it('serves the cluster pages under /parametres and keeps them out of the top navigation', function () {
    $this->get("/ecole/{$this->ecole->slug}/parametres/annee-scolaires")->assertOk();

    $nav = collect(Filament::getNavigation())->flatMap(fn ($g) => $g->getItems())->map(fn ($i) => $i->getLabel());
    expect($nav)->toContain('Paramètres')->not->toContain('Niveaux')->not->toContain('Champs personnalisés');
});

it('puts the settings at the bottom of the menu, in the Configuration group', function () {
    $groupes = collect(Filament::getNavigation())->map(fn ($g) => $g->getLabel());

    expect($groupes->last())->toBe('Configuration');
});

it('hides the cluster from a user who can open none of its pages', function () {
    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);

    expect(ParametresCluster::canAccess())->toBeFalse();
});
