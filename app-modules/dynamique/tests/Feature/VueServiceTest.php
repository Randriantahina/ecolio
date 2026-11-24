<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\VueService;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    $this->secretaire = utilisateurAvecRole(Role::Secretaire, $this->ecole);
    app(TenantContext::class)->forEcole($this->ecole->id);
    $this->vues = app(VueService::class);
    $this->etat = ['filtres' => ['statut' => ['value' => 'actif']], 'recherche' => 'Rakoto', 'tri' => 'nom:asc', 'colonnes' => []];
});

it('saves a personal view and lists it for its owner only', function () {
    $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, '6e A impayés', $this->etat);

    expect($this->vues->lister($this->secretaire, EntiteVue::Eleve))->toHaveCount(1)
        ->and($this->vues->lister($this->directeur, EntiteVue::Eleve))->toHaveCount(0);
});

it('keeps the saved state intact', function () {
    $vue = $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'Ma vue', $this->etat);

    expect($this->vues->charger($this->secretaire, $vue->uuid)->etat)->toBe($this->etat);
});

it('replaces the state when a view with the same name is saved again', function () {
    $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'Ma vue', $this->etat);
    $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'Ma vue', ['tri' => 'matricule:desc'] + $this->etat);

    $vues = $this->vues->lister($this->secretaire, EntiteVue::Eleve);
    expect($vues)->toHaveCount(1)->and($vues->first()->etat['tri'])->toBe('matricule:desc');
});

it('lets the directeur share a view with the whole school', function () {
    $this->vues->enregistrer($this->directeur, EntiteVue::Eleve, 'Impayés septembre', $this->etat, partagee: true);

    expect($this->vues->lister($this->secretaire, EntiteVue::Eleve))->toHaveCount(1);
});

it('refuses a secretaire sharing a view: they only have personal views (spec section 1)', function () {
    expect(fn () => $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'X', $this->etat, partagee: true))
        ->toThrow(ValidationException::class);
});

it('keeps views of different entities apart', function () {
    $this->vues->enregistrer($this->directeur, EntiteVue::Eleve, 'A', $this->etat);

    expect($this->vues->lister($this->directeur, EntiteVue::Absence))->toHaveCount(0);
});

it('has at most one default view per user and entity', function () {
    $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'A', $this->etat, parDefaut: true);
    $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'B', $this->etat, parDefaut: true);

    expect($this->vues->parDefaut($this->secretaire, EntiteVue::Eleve)->nom)->toBe('B');
});

it('deletes a view only for its owner or the directeur', function () {
    $vue = $this->vues->enregistrer($this->secretaire, EntiteVue::Eleve, 'A', $this->etat);
    $autre = utilisateurAvecRole(Role::Secretaire, $this->ecole);
    $partagee = $this->vues->enregistrer($this->directeur, EntiteVue::Eleve, 'P', $this->etat, partagee: true);

    expect(fn () => $this->vues->supprimer($autre, $vue->uuid))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $this->vues->supprimer($autre, $partagee->uuid))->toThrow(AuthorizationException::class);

    $this->vues->supprimer($this->secretaire, $vue->uuid);
    $this->vues->supprimer($this->directeur, $partagee->uuid);
    expect($this->vues->lister($this->directeur, EntiteVue::Eleve))->toHaveCount(0);
});

it('never exposes the views of another school (tenant isolation)', function () {
    $this->vues->enregistrer($this->directeur, EntiteVue::Eleve, 'A', $this->etat, partagee: true);
    $autre = Ecole::factory()->create();
    $dirAutre = utilisateurAvecRole(Role::Directeur, $autre);

    expect($this->vues->lister($dirAutre, EntiteVue::Eleve))->toHaveCount(0);
});
