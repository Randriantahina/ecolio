<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', ['libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30']);
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e'])->json('niveau');
    $this->classeA = $this->postJson('/api/v1/classes', ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid'], 'effectif_max' => 1])->json('classe');
    $this->classeB = $this->postJson('/api/v1/classes', ['nom' => '6e B', 'niveau_uuid' => $niveau['uuid']])->json('classe');
});

it('generates consecutive matricules per school when none is given', function () {
    $a = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto'])->assertCreated()->json('eleve.matricule');
    $b = $this->postJson('/api/v1/eleves', ['nom' => 'Rabe'])->json('eleve.matricule');

    expect($a)->toBe(now()->year.'-0001')->and($b)->toBe(now()->year.'-0002');
});

it('honours the school matricule format', function () {
    $this->ecole->update(['format_matricule' => ['prefixe' => 'LP', 'avec_annee' => false, 'longueur' => 5]]);

    $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto'])->assertJsonPath('eleve.matricule', 'LP00001');
});

it('keeps a matricule given by the school and refuses a duplicate in the same school (RG-E1)', function () {
    $this->postJson('/api/v1/eleves', ['nom' => 'A', 'matricule' => 'M-100'])->assertCreated();

    $this->postJson('/api/v1/eleves', ['nom' => 'B', 'matricule' => 'M-100'])
        ->assertUnprocessable()->assertJsonValidationErrors('matricule');
});

it('lets two schools use the same matricule', function () {
    $this->postJson('/api/v1/eleves', ['nom' => 'A', 'matricule' => 'M-100'])->assertCreated();

    app(TenantContext::class)->runAs($this->autre->id, fn () => Eleve::create(['nom' => 'Z', 'matricule' => 'M-100']));

    expect(Eleve::withoutGlobalScopes()->where('matricule', 'M-100')->count())->toBe(2);
});

it('warns on a likely duplicate and creates it only once confirmed (RG-E4)', function () {
    $base = ['nom' => 'Rakoto', 'prenoms' => 'Jean', 'date_naissance' => '2014-05-02'];
    $this->postJson('/api/v1/eleves', $base)->assertCreated();

    $this->postJson('/api/v1/eleves', $base)->assertStatus(409)->assertJsonPath('code', 'doublon_eleve');
    $this->postJson('/api/v1/eleves', $base + ['confirmer_doublon' => true])->assertCreated();
});

it('enrols into a class at creation and shows it in the list', function () {
    $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'classe_uuid' => $this->classeB['uuid']])->assertCreated();

    $this->getJson('/api/v1/eleves')->assertJsonPath('data.0.classe.nom', '6e B');
});

it('allows one active enrolment per pupil and year (RG-I1)', function () {
    $uuid = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'classe_uuid' => $this->classeA['uuid']])->json('eleve.uuid');

    $this->postJson("/api/v1/eleves/{$uuid}/inscriptions", ['classe_uuid' => $this->classeB['uuid']])
        ->assertUnprocessable()->assertJsonValidationErrors('classe_uuid');
});

it('warns but does not block when a class exceeds its maximum (RG-I2)', function () {
    $this->postJson('/api/v1/eleves', ['nom' => 'Un', 'classe_uuid' => $this->classeA['uuid']])
        ->assertJsonPath('avertissements.effectif_depasse', false);

    $this->postJson('/api/v1/eleves', ['nom' => 'Deux', 'classe_uuid' => $this->classeA['uuid']])
        ->assertCreated()->assertJsonPath('avertissements.effectif_depasse', true);
});

it('moves a pupil to another class and keeps the history', function () {
    $uuid = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'classe_uuid' => $this->classeA['uuid']])->json('eleve.uuid');

    $this->patchJson("/api/v1/eleves/{$uuid}/classe", ['classe_uuid' => $this->classeB['uuid']])
        ->assertOk()->assertJsonPath('eleve.classe.nom', '6e B');

    expect(DB::table('historique_classes')->count())->toBe(1);
});

it('reserves matricule correction to the directeur (RG-E1)', function () {
    $uuid = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'matricule' => 'M-1'])->json('eleve.uuid');

    Sanctum::actingAs(utilisateurAvecRole(Role::Secretaire, $this->ecole));
    $this->patchJson("/api/v1/eleves/{$uuid}", ['matricule' => 'M-2'])->assertForbidden();
    $this->patchJson("/api/v1/eleves/{$uuid}", ['nom' => 'Rakotoson'])->assertOk();

    Sanctum::actingAs($this->directeur);
    $this->patchJson("/api/v1/eleves/{$uuid}", ['matricule' => 'M-2'])->assertOk()->assertJsonPath('eleve.matricule', 'M-2');
});

it('searches by matricule or name, case-insensitively', function () {
    $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'prenoms' => 'Jean', 'matricule' => 'M-1']);
    $this->postJson('/api/v1/eleves', ['nom' => 'Rabe', 'prenoms' => 'Sofia', 'matricule' => 'M-2']);

    expect(collect($this->getJson('/api/v1/eleves?q=SOFIA')->json('data'))->pluck('nom')->all())->toBe(['Rabe'])
        ->and(collect($this->getJson('/api/v1/eleves?q=m-1')->json('data'))->pluck('nom')->all())->toBe(['Rakoto']);
});

it('never shows or touches another school pupil (tenant isolation)', function () {
    $etranger = app(TenantContext::class)->runAs($this->autre->id, fn () => Eleve::create(['nom' => 'Secret', 'matricule' => 'X-1']));

    expect(collect($this->getJson('/api/v1/eleves')->json('data'))->pluck('nom'))->not->toContain('Secret');
    $this->getJson("/api/v1/eleves/{$etranger->uuid}")->assertNotFound();
    $this->patchJson("/api/v1/eleves/{$etranger->uuid}", ['nom' => 'Pirate'])->assertNotFound();
});

it('lets enseignants read but not write, and keeps parents out', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Enseignant, $this->ecole));
    $this->getJson('/api/v1/eleves')->assertOk();
    $this->postJson('/api/v1/eleves', ['nom' => 'X'])->assertForbidden();

    Sanctum::actingAs(utilisateurAvecRole(Role::Parent, $this->ecole));
    $this->getJson('/api/v1/eleves')->assertForbidden();
});
