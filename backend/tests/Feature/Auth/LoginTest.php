<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->user = Utilisateur::factory()->for($this->ecole)->create([
        'email' => 'directrice@ecole.mg',
        'telephone' => '034 11 222 33',
        'mot_de_passe' => 'correct-horse',
    ]);
});

it('logs in with e-mail and returns a token plus the profile (US1.1)', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'directrice@ecole.mg',
        'password' => 'correct-horse',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'utilisateur' => ['uuid', 'nom', 'email', 'roles', 'ecole']])
        ->assertJsonPath('utilisateur.uuid', $this->user->uuid);
});

it('logs in with a phone number whatever the spacing', function () {
    $this->postJson('/api/v1/auth/login', ['login' => '0341122233', 'password' => 'correct-horse'])
        ->assertOk();
});

it('issues a token valid for 30 days so a power cut does not force a new login (RG-A3)', function () {
    Carbon::setTestNow('2026-11-23 08:00:00');

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])
        ->assertOk();

    $expires = $this->user->tokens()->first()->expires_at;
    expect($expires->equalTo(now()->addDays(30)))->toBeTrue();

    Carbon::setTestNow();
});

it('gives the same answer for a wrong password and an unknown account so accounts cannot be enumerated', function () {
    $wrongPassword = $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'nope']);
    $unknown = $this->postJson('/api/v1/auth/login', ['login' => 'ghost@ecole.mg', 'password' => 'nope']);

    $wrongPassword->assertStatus(422);
    $unknown->assertStatus(422);
    expect($wrongPassword->json('errors.login'))->toBe($unknown->json('errors.login'));
});

it('locks the account after five failures, even for the right password (RG-A2)', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'bad']);
    }

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])
        ->assertStatus(423)
        ->assertJsonStructure(['message']);
});

it('lets the user in again once the lock has expired', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'bad']);
    }

    Carbon::setTestNow(now()->addMinutes(16));

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])
        ->assertOk();

    Carbon::setTestNow();
});

it('resets the failure counter and records the login time on success', function () {
    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'bad']);
    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])
        ->assertOk();

    $fresh = $this->user->fresh();
    expect($fresh->tentatives_echouees)->toBe(0)
        ->and($fresh->derniere_connexion)->not->toBeNull();
});

it('refuses a deactivated account without revealing why', function () {
    $this->user->update(['actif' => false]);

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])
        ->assertStatus(422);
});

it('validates the payload with the API error format', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['login', 'password']]);
});
