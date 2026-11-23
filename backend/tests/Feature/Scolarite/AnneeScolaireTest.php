<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Platform\Models\Ecole;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function annee(Ecole $ecole, array $attributs = []): AnneeScolaire
{
    return app(TenantContext::class)->runAs($ecole->id, fn () => AnneeScolaire::create($attributs + [
        'libelle' => '2026-2027',
        'date_debut' => '2026-09-01',
        'date_fin' => '2027-06-30',
        'est_courante' => false,
    ]));
}

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/annees-scolaires')->assertUnauthorized();
});

it('lists only the years of the user school (tenant isolation)', function () {
    annee($this->ecole, ['libelle' => 'Notre annee']);
    annee($this->autre, ['libelle' => 'Annee etrangere']);
    Sanctum::actingAs($this->directeur);

    $libelles = collect($this->getJson('/api/v1/annees-scolaires')->assertOk()->json('data'))->pluck('libelle');

    expect($libelles)->toContain('Notre annee')->not->toContain('Annee etrangere');
});

it('lets directeur, secretaire and enseignant read the years', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->getJson('/api/v1/annees-scolaires')->assertOk();
})->with([Role::Directeur, Role::Secretaire, Role::Enseignant]);

it('keeps parents out of the school calendar', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Parent, $this->ecole));

    $this->getJson('/api/v1/annees-scolaires')->assertForbidden();
});

it('only lets the directeur change years (spec section 1)', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->postJson('/api/v1/annees-scolaires', [])->assertForbidden();
})->with([Role::Secretaire, Role::Enseignant, Role::Parent]);

it('creates a year and exposes a uuid, never the numeric id', function () {
    Sanctum::actingAs($this->directeur);

    $response = $this->postJson('/api/v1/annees-scolaires', [
        'libelle' => '2026-2027',
        'date_debut' => '2026-09-01',
        'date_fin' => '2027-06-30',
    ])->assertCreated();

    expect($response->json('annee_scolaire.uuid'))->toBeString()
        ->and($response->json('annee_scolaire'))->not->toHaveKey('id')
        ->and(AnneeScolaire::withoutGlobalScopes()->first()->ecole_id)->toBe($this->ecole->id);
});

it('makes the very first year current so the school is never left without one', function () {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', [
        'libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
    ])->assertCreated()->assertJsonPath('annee_scolaire.est_courante', true);
});

it('keeps a later year as not current unless asked (RG-S1)', function () {
    annee($this->ecole, ['est_courante' => true]);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', [
        'libelle' => '2027-2028', 'date_debut' => '2027-09-01', 'date_fin' => '2028-06-30',
    ])->assertCreated()->assertJsonPath('annee_scolaire.est_courante', false);
});

it('validates the payload', function (array $payload, string $champ) {
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', $payload)->assertUnprocessable()->assertJsonValidationErrors($champ);
})->with([
    'missing label' => [['date_debut' => '2026-09-01', 'date_fin' => '2027-06-30'], 'libelle'],
    'end before start' => [['libelle' => 'X', 'date_debut' => '2027-06-30', 'date_fin' => '2026-09-01'], 'date_fin'],
    'not a date' => [['libelle' => 'X', 'date_debut' => 'demain', 'date_fin' => '2027-06-30'], 'date_debut'],
]);

it('refuses a second year with the same label in the same school', function () {
    annee($this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', [
        'libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
    ])->assertUnprocessable()->assertJsonValidationErrors('libelle');
});

it('accepts the same label in another school, since uniqueness is per school', function () {
    annee($this->autre);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', [
        'libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
    ])->assertCreated();
});

it('updates the label and dates', function () {
    $annee = annee($this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->patchJson("/api/v1/annees-scolaires/{$annee->uuid}", ['libelle' => 'Rentree 2026', 'date_fin' => '2027-07-15'])
        ->assertOk()
        ->assertJsonPath('annee_scolaire.libelle', 'Rentree 2026')
        ->assertJsonPath('annee_scolaire.date_fin', '2027-07-15');
});

it('answers 404 for another school year so its existence is not revealed', function () {
    $etrangere = annee($this->autre);
    Sanctum::actingAs($this->directeur);

    $this->patchJson("/api/v1/annees-scolaires/{$etrangere->uuid}", ['libelle' => 'Pirate'])->assertNotFound();
    $this->postJson("/api/v1/annees-scolaires/{$etrangere->uuid}/courante")->assertNotFound();
});

it('switches the current year and demotes the previous one in one step (RG-S1)', function () {
    $ancienne = annee($this->ecole, ['libelle' => '2025-2026', 'est_courante' => true]);
    $nouvelle = annee($this->ecole, ['libelle' => '2026-2027']);
    Sanctum::actingAs($this->directeur);

    $this->postJson("/api/v1/annees-scolaires/{$nouvelle->uuid}/courante")
        ->assertOk()
        ->assertJsonPath('annee_scolaire.est_courante', true);

    $courantes = AnneeScolaire::withoutGlobalScopes()->where('ecole_id', $this->ecole->id)->where('est_courante', true)->pluck('uuid');
    expect($courantes->all())->toBe([$nouvelle->uuid])
        ->and($ancienne->fresh()->est_courante)->toBeFalse();
});

it('does not touch the current year of another school when switching', function () {
    $autreCourante = annee($this->autre, ['est_courante' => true]);
    $nouvelle = annee($this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->postJson("/api/v1/annees-scolaires/{$nouvelle->uuid}/courante")->assertOk();

    expect(AnneeScolaire::withoutGlobalScopes()->find($autreCourante->id)->est_courante)->toBeTrue();
});

it('forbids two current years in one school at database level', function () {
    annee($this->ecole, ['libelle' => 'A', 'est_courante' => true]);

    annee($this->ecole, ['libelle' => 'B', 'est_courante' => true]);
})->throws(QueryException::class);

it('allows each school its own current year at database level', function () {
    annee($this->ecole, ['est_courante' => true]);
    annee($this->autre, ['est_courante' => true]);

    expect(DB::table('annees_scolaires')->where('est_courante', true)->count())->toBe(2);
});

it('refuses an update that would end the year before it starts, even when only one date is sent', function () {
    $annee = annee($this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->patchJson("/api/v1/annees-scolaires/{$annee->uuid}", ['date_fin' => '2026-08-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date_fin');
});
