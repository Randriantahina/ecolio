<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create(['nom' => 'Ecole Pilote', 'adresse' => 'Antananarivo']);
    $this->autre = Ecole::factory()->create(['nom' => 'Autre Ecole']);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/ecole')->assertUnauthorized();
});

it('lets any school role read its own school', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->getJson('/api/v1/ecole')
        ->assertOk()
        ->assertJsonPath('ecole.nom', 'Ecole Pilote')
        ->assertJsonPath('ecole.devise', 'MGA');
})->with([Role::Directeur, Role::Secretaire, Role::Enseignant]);

it('never exposes the numeric id', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole));

    expect($this->getJson('/api/v1/ecole')->json('ecole'))->not->toHaveKey('id');
});

it('lets the directeur update the identity and receipt texts (US2.1)', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole));

    $this->putJson('/api/v1/ecole', [
        'nom' => 'Ecole Pilote 2',
        'adresse' => 'Ambohitsoa',
        'telephone' => '034 00 000 01',
        'email' => 'contact@pilote.mg',
        'mention_legale' => 'NIF 123456',
        'signature_recu' => 'La Direction',
    ])->assertOk()->assertJsonPath('ecole.mention_legale', 'NIF 123456');

    $fresh = $this->ecole->fresh();
    expect($fresh->nom)->toBe('Ecole Pilote 2')
        ->and($fresh->parametres['signature_recu'])->toBe('La Direction');
});

it('keeps the other receipt text when only one is sent', function () {
    $this->ecole->update(['parametres' => ['signature_recu' => 'La Direction']]);
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole));

    $this->putJson('/api/v1/ecole', ['nom' => 'Ecole Pilote', 'mention_legale' => 'NIF 9'])->assertOk();

    expect($this->ecole->fresh()->parametres)->toMatchArray(['signature_recu' => 'La Direction', 'mention_legale' => 'NIF 9']);
});

it('forbids non-directeur roles from changing settings', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->putJson('/api/v1/ecole', ['nom' => 'Hack'])->assertForbidden();

    expect($this->ecole->fresh()->nom)->toBe('Ecole Pilote');
})->with([Role::Secretaire, Role::Enseignant, Role::Parent]);

it('only ever touches the school of the caller', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole));

    $this->putJson('/api/v1/ecole', ['nom' => 'Renamed', 'id' => $this->autre->id, 'slug' => 'steal'])->assertOk();

    expect($this->autre->fresh()->nom)->toBe('Autre Ecole')
        ->and($this->ecole->fresh()->slug)->not->toBe('steal');
});

it('validates the payload', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole));

    $this->putJson('/api/v1/ecole', ['nom' => '', 'email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nom', 'email']);
});

it('answers 404 for a platform super admin, who has no school', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::SuperAdmin));

    $this->getJson('/api/v1/ecole')->assertNotFound();
});
