<?php

declare(strict_types=1);

use App\Domain\Audit\Models\JournalAudit;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    JournalAudit::query()->delete(); // setup noise is not under test
});

it('records who created a team member, in which school', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'Rasoa', 'email' => 'rasoa@ecole.mg', 'password' => 'motdepasse-solide', 'role' => 'secretaire',
    ])->assertCreated();

    $entry = JournalAudit::where('event', 'created')->where('subject_type', Utilisateur::class)->first();
    expect($entry)->not->toBeNull()
        ->and($entry->causer_id)->toBe($this->directeur->id)
        ->and($entry->ecole_id)->toBe($this->ecole->id);
});

it('never writes a password or its hash into the audit trail', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/utilisateurs', [
        'nom' => 'Rasoa', 'email' => 'rasoa@ecole.mg', 'password' => 'motdepasse-solide', 'role' => 'secretaire',
    ])->assertCreated();

    $dump = json_encode(JournalAudit::all()->toArray());
    expect($dump)->not->toContain('mot_de_passe')->not->toContain('motdepasse-solide');
});

it('records the old and new values when school settings change', function () {
    $this->ecole->update(['nom' => 'Ancien Nom']);
    JournalAudit::query()->delete();
    Sanctum::actingAs($this->directeur);

    $this->putJson('/api/v1/ecole', ['nom' => 'Nouveau Nom'])->assertOk();

    $entry = JournalAudit::where('subject_type', Ecole::class)->where('event', 'updated')->first();
    $changes = $entry->attribute_changes;
    expect($changes['attributes']['nom'])->toBe('Nouveau Nom')
        ->and($changes['old']['nom'])->toBe('Ancien Nom')
        ->and($entry->ecole_id)->toBe($this->ecole->id);
});

it('stays silent on login bookkeeping so the trail holds only meaningful events', function () {
    $this->directeur->update(['email' => 'directeur@ecole.mg', 'mot_de_passe' => 'secret-long']);
    JournalAudit::query()->delete();

    $this->postJson('/api/v1/auth/login', ['login' => 'directeur@ecole.mg', 'password' => 'secret-long'])->assertOk();

    expect(JournalAudit::count())->toBe(0);
});

it('lets the directeur read only the trail of their own school, newest first', function () {
    Sanctum::actingAs($this->directeur);
    $this->putJson('/api/v1/ecole', ['nom' => 'Premier'])->assertOk();
    $this->putJson('/api/v1/ecole', ['nom' => 'Second'])->assertOk();
    $foreign = utilisateurAvecRole(Role::Directeur, $this->autre);
    Sanctum::actingAs($foreign);
    $this->putJson('/api/v1/ecole', ['nom' => 'Chez Autrui'])->assertOk();
    Sanctum::actingAs($this->directeur);

    $rows = $this->getJson('/api/v1/audit')->assertOk()->json('data');

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['changes']['attributes']['nom'])->toBe('Second')
        ->and(json_encode($rows))->not->toContain('Chez Autrui');
});

it('forbids non-directeur roles from reading the trail (spec section 1)', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->getJson('/api/v1/audit')->assertForbidden();
})->with([Role::Secretaire, Role::Enseignant, Role::Parent]);

it('exposes no way to alter the trail through the API', function () {
    Sanctum::actingAs($this->directeur);
    $this->putJson('/api/v1/ecole', ['nom' => 'X'])->assertOk();
    $id = JournalAudit::first()->id;

    $this->putJson("/api/v1/audit/{$id}", [])->assertStatus(404);
    $this->deleteJson("/api/v1/audit/{$id}")->assertStatus(404);
});
