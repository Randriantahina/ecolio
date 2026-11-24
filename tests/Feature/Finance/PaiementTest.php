<?php

declare(strict_types=1);

use App\Domain\Finance\Models\Echeance;
use App\Domain\Finance\Models\Paiement;
use App\Domain\Identity\Enums\Role;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
    $this->directeur = utilisateurAvecRole(Role::Directeur, $this->ecole);
    Sanctum::actingAs($this->directeur);

    // Short school year (Sept-Nov) keeps the number of instalments small and predictable.
    $this->postJson('/api/v1/annees-scolaires', ['libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2026-11-30']);
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '6e'])->json('niveau');
    $this->classe = $this->postJson('/api/v1/classes', ['nom' => '6e A', 'niveau_uuid' => $niveau['uuid']])->json('classe');

    $ecolage = $this->postJson('/api/v1/types-frais', ['nom' => 'Écolage', 'periodicite' => 'mensuel'])->json('type_frais');
    $inscription = $this->postJson('/api/v1/types-frais', ['nom' => 'Inscription', 'periodicite' => 'unique'])->json('type_frais');
    $this->postJson('/api/v1/tarifs', ['type_frais_uuid' => $ecolage['uuid'], 'montant_ar' => 50_000])->assertCreated();
    $this->postJson('/api/v1/tarifs', ['type_frais_uuid' => $inscription['uuid'], 'niveau_uuid' => $niveau['uuid'], 'montant_ar' => 30_000])->assertCreated();

    $this->eleve = $this->postJson('/api/v1/eleves', ['nom' => 'Rakoto', 'classe_uuid' => $this->classe['uuid']])->json('eleve');
    $this->echeances = fn () => collect($this->getJson("/api/v1/eleves/{$this->eleve['uuid']}/echeances")->json('data'));
    $this->septembre = fn () => ($this->echeances)()->firstWhere('periode', '2026-09');
});

function encaisser($test, array $lignes, array $extra = [])
{
    return $test->postJson('/api/v1/paiements', [
        'eleve_uuid' => $test->eleve['uuid'],
        'lignes' => $lignes,
        'mode' => 'especes',
    ] + $extra);
}

it('generates one instalment per month plus the one-off fee at enrolment', function () {
    $libelles = ($this->echeances)()->pluck('libelle')->all();

    expect($libelles)->toContain('Écolage Septembre 2026', 'Écolage Octobre 2026', 'Écolage Novembre 2026', 'Inscription')
        ->and(($this->echeances)()->count())->toBe(4);
});

it('prefers the class tariff over the level tariff over the school tariff', function () {
    $niveau = $this->postJson('/api/v1/niveaux', ['nom' => '5e'])->json('niveau');
    $classe = $this->postJson('/api/v1/classes', ['nom' => '5e A', 'niveau_uuid' => $niveau['uuid']])->json('classe');
    $type = collect($this->getJson('/api/v1/types-frais')->json('data'))->firstWhere('nom', 'Écolage');
    $this->postJson('/api/v1/tarifs', ['type_frais_uuid' => $type['uuid'], 'classe_uuid' => $classe['uuid'], 'montant_ar' => 80_000])->assertCreated();

    $autre = $this->postJson('/api/v1/eleves', ['nom' => 'Rabe', 'classe_uuid' => $classe['uuid']])->json('eleve');
    $montants = collect($this->getJson("/api/v1/eleves/{$autre['uuid']}/echeances")->json('data'))->where('periode', '2026-09')->pluck('montant_ar');

    expect($montants->all())->toBe([80_000]);
});

it('pays an instalment, issues a numbered receipt and marks it paid (RG-P1)', function () {
    $e = ($this->septembre)();

    $response = encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000]])->assertCreated();

    expect($response->json('paiement.numero'))->toBe(now()->year.'-0001')
        ->and($response->json('paiement.total_ar'))->toBe(50_000)
        ->and($response->json('paiement.mention'))->toBe('Reçu de paiement')
        ->and(($this->septembre)()['statut'])->toBe('paye');
});

it('accepts a partial payment and keeps the instalment partial (RG-P2)', function () {
    $e = ($this->septembre)();

    encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 20_000]])->assertCreated();

    $apres = ($this->septembre)();
    expect($apres['statut'])->toBe('partiel')->and($apres['solde_ar'])->toBe(30_000);
});

it('refuses a line above the remaining balance', function () {
    $e = ($this->septembre)();
    encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 40_000]])->assertCreated();

    encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 20_000]])
        ->assertUnprocessable()->assertJsonValidationErrors('lignes.0.montant_ar');
});

it('takes several months in one receipt with several lines (RG-P3)', function () {
    $lignes = ($this->echeances)()->whereIn('periode', ['2026-09', '2026-10'])
        ->map(fn ($e) => ['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000])->values()->all();

    $response = encaisser($this, $lignes)->assertCreated();

    expect($response->json('paiement.lignes'))->toHaveCount(2)
        ->and($response->json('paiement.total_ar'))->toBe(100_000)
        ->and(Paiement::count())->toBe(1);
});

it('numbers receipts without gaps, even after a void (RG-R1, RG-R2)', function () {
    $e = ($this->echeances)()->whereNotNull('periode')->values();
    $un = encaisser($this, [['echeance_uuid' => $e[0]['uuid'], 'montant_ar' => 50_000]])->json('paiement');
    $deux = encaisser($this, [['echeance_uuid' => $e[1]['uuid'], 'montant_ar' => 50_000]])->json('paiement');

    $this->postJson("/api/v1/paiements/{$un['uuid']}/annulation", ['motif' => 'Erreur de saisie'])->assertOk();
    $trois = encaisser($this, [['echeance_uuid' => $e[2]['uuid'], 'montant_ar' => 50_000]])->json('paiement');

    $y = now()->year;
    expect([$un['numero'], $deux['numero'], $trois['numero']])->toBe(["$y-0001", "$y-0002", "$y-0003"]);
});

it('releases no number when a payment fails validation', function () {
    $e = ($this->septembre)();
    encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 999_999]])->assertUnprocessable();

    $ok = encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000]])->json('paiement.numero');

    expect($ok)->toBe(now()->year.'-0001');
});

it('replays an offline payment without paying twice (idempotency)', function () {
    $e = ($this->septembre)();
    $cle = (string) Str::uuid();
    $ligne = [['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000]];

    $premier = encaisser($this, $ligne, ['client_uuid' => $cle])->assertCreated();
    $rejeu = encaisser($this, $ligne, ['client_uuid' => $cle])->assertOk();

    expect($rejeu->json('paiement.uuid'))->toBe($premier->json('paiement.uuid'))
        ->and(Paiement::count())->toBe(1);
});

it('voids a payment: reason required, receipt keeps its number, balance comes back (RG-P4)', function () {
    $e = ($this->septembre)();
    $p = encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000]])->json('paiement');

    $this->postJson("/api/v1/paiements/{$p['uuid']}/annulation", [])->assertUnprocessable()->assertJsonValidationErrors('motif');
    $void = $this->postJson("/api/v1/paiements/{$p['uuid']}/annulation", ['motif' => 'Mauvais élève'])->assertOk();

    expect($void->json('paiement.annule'))->toBeTrue()
        ->and($void->json('paiement.numero'))->toBe($p['numero'])
        ->and(($this->septembre)()['statut'])->toBe('du');
});

it('refuses to void twice', function () {
    $p = encaisser($this, [['echeance_uuid' => ($this->septembre)()['uuid'], 'montant_ar' => 50_000]])->json('paiement');
    $this->postJson("/api/v1/paiements/{$p['uuid']}/annulation", ['motif' => 'Erreur'])->assertOk();

    $this->postJson("/api/v1/paiements/{$p['uuid']}/annulation", ['motif' => 'Encore'])->assertUnprocessable();
});

it('lets the secretaire take payments but not void them (RG-P7)', function () {
    $secretaire = utilisateurAvecRole(Role::Secretaire, $this->ecole);
    Sanctum::actingAs($secretaire);

    $p = encaisser($this, [['echeance_uuid' => ($this->septembre)()['uuid'], 'montant_ar' => 50_000]])->assertCreated()->json('paiement');

    $this->postJson("/api/v1/paiements/{$p['uuid']}/annulation", ['motif' => 'Erreur'])->assertForbidden();
});

it('keeps enseignants and parents away from money', function (Role $role) {
    Sanctum::actingAs(utilisateurAvecRole($role, $this->ecole));

    $this->postJson('/api/v1/paiements', [])->assertForbidden();
    $this->getJson("/api/v1/classes/{$this->classe['uuid']}/registre-ecolage")->assertForbidden();
})->with([Role::Enseignant, Role::Parent]);

it('never lets school A pay or read an instalment of school B (tenant isolation)', function () {
    $etranger = ($this->septembre)();
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->autre));

    $this->postJson('/api/v1/paiements', [
        'eleve_uuid' => $this->eleve['uuid'],
        'lignes' => [['echeance_uuid' => $etranger['uuid'], 'montant_ar' => 1000]],
        'mode' => 'especes',
    ])->assertNotFound();
    expect(Echeance::count())->toBe(0);
});

it('shows the register with one row per pupil and the box state for a month', function () {
    $autre = $this->postJson('/api/v1/eleves', ['nom' => 'Rabe', 'classe_uuid' => $this->classe['uuid']])->json('eleve');
    encaisser($this, [['echeance_uuid' => ($this->septembre)()['uuid'], 'montant_ar' => 50_000]]);

    $rows = collect($this->getJson("/api/v1/classes/{$this->classe['uuid']}/registre-ecolage?periode=2026-09")->assertOk()->json('data'));

    expect($rows)->toHaveCount(2)
        ->and($rows->firstWhere('eleve.nom', 'Rakoto')['echeances'][0]['statut'])->toBe('paye')
        ->and($rows->firstWhere('eleve.nom', 'Rakoto')['echeances'][0]['recu'])->toBe(now()->year.'-0001')
        ->and($rows->firstWhere('eleve.nom', 'Rakoto')['echeances'][0]['paiement_uuid'])->toBeString()
        ->and($rows->firstWhere('eleve.uuid', $autre['uuid'])['echeances'][0]['statut'])->toBe('du');
});

it('reports the remaining balance on the receipt (RG-R4)', function () {
    $e = ($this->septembre)();

    $p = encaisser($this, [['echeance_uuid' => $e['uuid'], 'montant_ar' => 50_000]])->json('paiement');

    // 3 monthly fees + registration = 180 000; 50 000 paid.
    expect($p['reste_a_payer_ar'])->toBe(130_000);
});

it('downloads the receipt as an A5 PDF named after its number (RG-R4)', function () {
    $p = encaisser($this, [['echeance_uuid' => ($this->septembre)()['uuid'], 'montant_ar' => 50_000]])->json('paiement');

    $response = $this->get("/api/v1/paiements/{$p['uuid']}/pdf")->assertOk();

    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('recu-'.$p['numero'].'.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('refuses the PDF of another school receipt (tenant isolation)', function () {
    $p = encaisser($this, [['echeance_uuid' => ($this->septembre)()['uuid'], 'montant_ar' => 50_000]])->json('paiement');
    Sanctum::actingAs(utilisateurAvecRole(Role::Directeur, $this->autre));

    $this->get("/api/v1/paiements/{$p['uuid']}/pdf")->assertNotFound();
});

it('tells in the tariff list whether a tariff targets the whole school, a level or a class', function () {
    $liste = collect($this->getJson('/api/v1/tarifs')->assertOk()->json('data'));

    $inscription = $liste->firstWhere('type_frais.nom', 'Inscription');
    $ecolage = $liste->firstWhere('type_frais.nom', 'Écolage');

    expect($inscription['niveau']['nom'])->toBe('6e')->and($inscription['classe'])->toBeNull()
        ->and($ecolage['niveau'])->toBeNull()->and($ecolage['classe'])->toBeNull();
});
