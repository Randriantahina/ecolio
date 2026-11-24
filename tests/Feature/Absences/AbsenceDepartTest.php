<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    Sanctum::actingAs($this->directeur);

    $this->postJson('/api/v1/annees-scolaires', ['libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2026-12-31']);
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e'])->json('niveau');
    $this->classe = $this->postJson('/api/v1/classes', ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid']])->json('classe');
    $this->autreClasse = $this->postJson('/api/v1/classes', ['nom' => '6e B', 'niveau_uuid' => $niveau['uuid']])->json('classe');
    $type = $this->postJson('/api/v1/types-frais', ['nom' => 'Écolage', 'periodicite' => 'mensuel'])->json('type_frais');
    $this->postJson('/api/v1/tarifs', ['type_frais_uuid' => $type['uuid'], 'montant_ar' => 50_000]);

    $this->a = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'classe_uuid' => $this->classe['uuid']])->json('eleve');
    $this->b = $this->postJson('/api/v1/eleves', ['nom' => 'Rabe', 'classe_uuid' => $this->classe['uuid']])->json('eleve');
});

it('stores exactly the absentees sent and removes the others (RG-P5)', function () {
    $url = "/api/v1/classes/{$this->classe['uuid']}/absences";

    $this->putJson($url, ['date' => '2026-09-10', 'absents' => [['eleve_uuid' => $this->a['uuid']], ['eleve_uuid' => $this->b['uuid']]]])
        ->assertOk()->assertJsonCount(2, 'data');
    $this->putJson($url, ['date' => '2026-09-10', 'absents' => [['eleve_uuid' => $this->b['uuid'], 'justifiee' => true]]])
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.justifiee', true);

    expect($this->getJson("$url?date=2026-09-10")->json('data.0.matricule'))->toBe($this->b['matricule']);
});

it('keeps one absence per pupil, day and period', function () {
    $url = "/api/v1/classes/{$this->classe['uuid']}/absences";
    $body = ['date' => '2026-09-10', 'absents' => [['eleve_uuid' => $this->a['uuid']]]];

    $this->putJson($url, $body);
    $this->putJson($url, $body);

    expect(DB::table('absences')->count())->toBe(1);
});

it('treats morning and afternoon as separate periods', function () {
    $url = "/api/v1/classes/{$this->classe['uuid']}/absences";

    $this->putJson($url, ['date' => '2026-09-10', 'periode' => 'matin', 'absents' => [['eleve_uuid' => $this->a['uuid']]]]);
    $this->putJson($url, ['date' => '2026-09-10', 'periode' => 'apres_midi', 'absents' => [['eleve_uuid' => $this->a['uuid']]]]);

    expect(DB::table('absences')->count())->toBe(2);
});

it('refuses a pupil who is not in the class', function () {
    $this->putJson("/api/v1/classes/{$this->autreClasse['uuid']}/absences", ['date' => '2026-09-10', 'absents' => [['eleve_uuid' => $this->a['uuid']]]])
        ->assertUnprocessable();
});

it('ignores an offline replay carrying a known client_uuid', function () {
    $url = "/api/v1/classes/{$this->classe['uuid']}/absences";
    $cle = (string) Str::uuid();

    $this->putJson($url, ['date' => '2026-09-10', 'absents' => [['eleve_uuid' => $this->a['uuid'], 'client_uuid' => $cle]]])->assertOk();
    DB::table('absences')->delete();
    $this->putJson($url, ['date' => '2026-09-11', 'absents' => [['eleve_uuid' => $this->a['uuid'], 'client_uuid' => $cle]]])->assertOk();

    expect(DB::table('absences')->count())->toBe(1);
});

it('lets enseignants take attendance but keeps parents out', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Enseignant, $this->ecole));
    $this->putJson("/api/v1/classes/{$this->classe['uuid']}/absences", ['date' => '2026-09-10', 'absents' => []])->assertOk();

    Sanctum::actingAs(utilisateurAvecRole(Role::Parent, $this->ecole));
    $this->putJson("/api/v1/classes/{$this->classe['uuid']}/absences", ['date' => '2026-09-10', 'absents' => []])->assertForbidden();
});

it('hides another school class', function () {
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->autre));

    $this->putJson("/api/v1/classes/{$this->classe['uuid']}/absences", ['date' => '2026-09-10', 'absents' => []])->assertNotFound();
});

it('records a departure and cancels only the future unpaid months (RG-P6)', function () {
    $echeances = collect($this->getJson("/api/v1/eleves/{$this->a['uuid']}/echeances")->json('data'));
    // Pay September in full and October partially; leave November and December untouched.
    $this->postJson('/api/v1/paiements', ['eleve_uuid' => $this->a['uuid'], 'mode' => 'especes', 'lignes' => [
        ['echeance_uuid' => $echeances->firstWhere('periode', '2026-09')['uuid'], 'montant_ar' => 50_000],
        ['echeance_uuid' => $echeances->firstWhere('periode', '2026-10')['uuid'], 'montant_ar' => 10_000],
    ]])->assertCreated();

    $this->postJson("/api/v1/eleves/{$this->a['uuid']}/sortie", ['statut' => 'sorti', 'date_sortie' => '2026-10-15', 'motif_sortie' => 'demenagement'])
        ->assertOk()->assertJsonPath('eleve.statut', 'sorti');

    $apres = collect($this->getJson("/api/v1/eleves/{$this->a['uuid']}/echeances")->json('data'))->keyBy('periode');
    expect($apres['2026-09']['statut'])->toBe('paye')
        ->and($apres['2026-10']['statut'])->toBe('partiel')
        ->and($apres['2026-10']['solde_ar'])->toBe(40_000)
        ->and($apres['2026-11']['statut'])->toBe('annule')
        ->and($apres['2026-12']['statut'])->toBe('annule');
});

it('keeps a pupil who left and refuses a second departure', function () {
    $url = "/api/v1/eleves/{$this->a['uuid']}/sortie";
    $this->postJson($url, ['statut' => 'abandon', 'date_sortie' => '2026-10-01'])->assertOk();

    $this->getJson("/api/v1/eleves/{$this->a['uuid']}")->assertOk()->assertJsonPath('eleve.statut', 'abandon');
    $this->postJson($url, ['statut' => 'sorti', 'date_sortie' => '2026-10-02'])->assertUnprocessable();
});

it('refuses a departure that leaves the pupil active', function () {
    $this->postJson("/api/v1/eleves/{$this->a['uuid']}/sortie", ['statut' => 'actif', 'date_sortie' => '2026-10-01'])->assertUnprocessable();
});
