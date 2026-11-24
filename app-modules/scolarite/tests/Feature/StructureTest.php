<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Administration, $this->ecole);
    Sanctum::actingAs($this->directeur);
    $this->postJson('/api/v1/annees-scolaires', ['libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30']);
});

it('creates a level and a class in the current year', function () {
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e', 'cycle' => 'college', 'ordre' => 6])->assertCreated()->json('niveau');

    $this->postJson('/api/v1/classes', ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid'], 'effectif_max' => 40])
        ->assertCreated()
        ->assertJsonPath('classe.niveau.nom', '6e');

    expect($this->getJson('/api/v1/classes')->json('data.0.nom'))->toBe('6e A');
});

it('refuses two classes with the same name in one year', function () {
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e'])->json('niveau');
    $payload = ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid']];
    $this->postJson('/api/v1/classes', $payload)->assertCreated();

    $this->postJson('/api/v1/classes', $payload)->assertUnprocessable()->assertJsonValidationErrors('nom');
});

it('keeps levels and classes private to the school', function () {
    $etranger = app(TenantContext::class)->runAs($this->autre->id, fn () => Niveau::create(['nom' => 'Secret']));

    expect(collect($this->getJson('/api/v1/niveaux')->json('data'))->pluck('nom'))->not->toContain('Secret');
    $this->patchJson("/api/v1/niveaux/{$etranger->uuid}", ['nom' => 'Pirate'])->assertNotFound();
    $this->postJson('/api/v1/classes', ['nom' => 'X', 'niveau_uuid' => $etranger->uuid])->assertNotFound();
});

it('keeps enseignants out of the school structure', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Enseignant, $this->ecole));

    $this->getJson('/api/v1/niveaux')->assertForbidden();
    $this->postJson('/api/v1/niveaux', ['nom' => 'CP'])->assertForbidden();
    $this->postJson('/api/v1/classes', [])->assertForbidden();
});

it('soft deletes a class', function () {
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e'])->json('niveau');
    $classe = $this->postJson('/api/v1/classes', ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid']])->json('classe');

    $this->deleteJson("/api/v1/classes/{$classe['uuid']}")->assertNoContent();

    expect(Classe::withoutGlobalScopes()->withTrashed()->count())->toBe(1)
        ->and($this->getJson('/api/v1/classes')->json('data'))->toBe([]);
});
