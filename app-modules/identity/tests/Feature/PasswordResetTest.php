<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = Utilisateur::factory()->for(Ecole::factory())->create([
        'email' => 'directrice@ecole.mg',
        'mot_de_passe' => 'ancien-secret',
    ]);
});

it('sends a reset link to a known e-mail (US1.2)', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'directrice@ecole.mg'])->assertOk();

    Notification::assertSentTo($this->user, ResetPassword::class);
});

it('points the link at the SPA, not at an API route', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://app.ecolio.test']);

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'directrice@ecole.mg']);

    Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) {
        $url = $notification->toMail($this->user)->actionUrl;

        return str_starts_with($url, 'https://app.ecolio.test/reinitialiser-mot-de-passe')
            && str_contains($url, 'email=directrice%40ecole.mg');
    });
});

it('answers identically for an unknown e-mail so accounts cannot be enumerated', function () {
    Notification::fake();

    $known = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'directrice@ecole.mg']);
    $unknown = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'ghost@ecole.mg']);

    expect($unknown->status())->toBe($known->status())
        ->and($unknown->json())->toBe($known->json());
    Notification::assertSentToTimes($this->user, ResetPassword::class, 1);
    Notification::assertCount(1);
});

it('validates the e-mail format', function () {
    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nope'])
        ->assertStatus(422)->assertJsonValidationErrors('email');
});

it('resets the password with a valid token, then the new one works', function () {
    $token = Password::createToken($this->user);

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'directrice@ecole.mg',
        'token' => $token,
        'password' => 'nouveau-secret',
        'password_confirmation' => 'nouveau-secret',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'nouveau-secret'])->assertOk();
    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'ancien-secret'])->assertStatus(422);
});

it('revokes every session and clears a lockout on reset, since the old credentials may be compromised', function () {
    $this->user->createToken('phone');
    foreach (range(1, 5) as $_) {
        $this->user->enregistrerEchec();
    }
    $this->user->save();
    $token = Password::createToken($this->user);

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'directrice@ecole.mg', 'token' => $token,
        'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret',
    ])->assertOk();

    $fresh = $this->user->fresh();
    expect($fresh->tokens()->count())->toBe(0)
        ->and($fresh->estVerrouille())->toBeFalse();
});

it('rejects an invalid token and leaves the password unchanged', function () {
    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'directrice@ecole.mg', 'token' => 'wrong',
        'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret',
    ])->assertStatus(422)->assertJsonValidationErrors('token');

    $this->postJson('/api/v1/auth/login', ['login' => 'directrice@ecole.mg', 'password' => 'ancien-secret'])->assertOk();
});

it('refuses a token a second time', function () {
    $token = Password::createToken($this->user);
    $payload = [
        'email' => 'directrice@ecole.mg', 'token' => $token,
        'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret',
    ];

    $this->postJson('/api/v1/auth/password/reset', $payload)->assertOk();
    $this->postJson('/api/v1/auth/password/reset', $payload)->assertStatus(422);
});

it('enforces the minimum length and the confirmation (RG-A2)', function () {
    $token = Password::createToken($this->user);

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'directrice@ecole.mg', 'token' => $token,
        'password' => 'short', 'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});
