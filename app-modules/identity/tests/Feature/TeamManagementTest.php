<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/utilisateurs')->assertUnauthorized();
});

it('lists only the team of the directeur school', function () {
    Utilisateur::factory()->for($this->ecole)->create(['nom' => 'Collegue']);
    Utilisateur::factory()->for($this->autre)->create(['nom' => 'Etranger']);
    Sanctum::actingAs($this->directeur);

    $noms = collect($this->getJson('/api/v1/utilisateurs')->assertOk()->json('data'))->pluck('nom');

    expect($noms)->toContain('Collegue')->not->toContain('Etranger');
});

it('forbids non-directeur roles from managing the team (spec section 1)', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->getJson('/api/v1/utilisateurs')->assertForbidden();
    $this->postJson('/api/v1/utilisateurs', [])->assertForbidden();
})->with([Role::Secretaire, Role::Enseignant, Role::Parent]);

it('creates a team member in the directeur school with the chosen role (US1.3)', function () {
    Sanctum::actingAs($this->directeur);

    $response = $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'Rasoa Secretaire',
        'email' => 'rasoa@ecole.mg',
        'password' => 'motdepasse-solide',
        'role' => 'secretaire',
    ])->assertCreated();

    $created = Utilisateur::where('email', 'rasoa@ecole.mg')->first();
    expect($created->ecole_id)->toBe($this->ecole->id)
        ->and($created->aRole(Role::Secretaire))->toBeTrue()
        ->and(Hash::check('motdepasse-solide', $created->mot_de_passe))->toBeTrue()
        ->and($response->json('utilisateur.roles'))->toBe(['secretaire']);
});

it('ignores an ecole_id sent by the client so a directeur cannot plant users elsewhere', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'Intrus',
        'email' => 'intrus@ecole.mg',
        'password' => 'motdepasse-solide',
        'role' => 'enseignant',
        'ecole_id' => $this->autre->id,
    ])->assertCreated();

    expect(Utilisateur::where('email', 'intrus@ecole.mg')->value('ecole_id'))->toBe($this->ecole->id);
});

it('refuses to create a super admin from the school app', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'Root',
        'email' => 'root@ecole.mg',
        'password' => 'motdepasse-solide',
        'role' => 'super_admin',
    ])->assertStatus(422)->assertJsonValidationErrors('role');
});

it('enforces the minimum password length (RG-A2)', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'X', 'email' => 'x@ecole.mg', 'password' => 'short', 'role' => 'parent',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('requires an e-mail or a phone number', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'X', 'password' => 'motdepasse-solide', 'role' => 'parent',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects an e-mail already used', function () {
    Utilisateur::factory()->create(['email' => 'dup@ecole.mg']);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'X', 'email' => 'dup@ecole.mg', 'password' => 'motdepasse-solide', 'role' => 'parent',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('deactivates a member and revokes their tokens so access ends immediately', function () {
    $membre = utilisateurAvecRole(Role::Secretaire, $this->ecole);
    $membre->createToken('phone');
    Sanctum::actingAs($this->directeur);

    $this->patchJson("/api/v1/utilisateurs/{$membre->uuid}", ['actif' => false])->assertOk();

    expect($membre->fresh()->actif)->toBeFalse()
        ->and($membre->tokens()->count())->toBe(0);
});

it('answers 404 for a member of another school, never leaking that they exist', function () {
    $etranger = Utilisateur::factory()->for($this->autre)->create();
    Sanctum::actingAs($this->directeur);

    $this->patchJson("/api/v1/utilisateurs/{$etranger->uuid}", ['actif' => false])->assertNotFound();

    expect($etranger->fresh()->actif)->toBeTrue();
});
