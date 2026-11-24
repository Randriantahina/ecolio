<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Utilisateur;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-11-23 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('is not locked before the fifth failed attempt (RG-A2)', function () {
    $user = new Utilisateur;

    foreach (range(1, 4) as $_) {
        $user->enregistrerEchec();
    }

    expect($user->estVerrouille())->toBeFalse()
        ->and($user->tentatives_echouees)->toBe(4);
});

it('locks for 15 minutes on the fifth failed attempt', function () {
    $user = new Utilisateur;

    foreach (range(1, 5) as $_) {
        $user->enregistrerEchec();
    }

    expect($user->estVerrouille())->toBeTrue()
        ->and($user->verrouille_jusqua->equalTo(now()->addMinutes(15)))->toBeTrue();
});

it('unlocks once the lock has expired', function () {
    $user = new Utilisateur;
    foreach (range(1, 5) as $_) {
        $user->enregistrerEchec();
    }

    Carbon::setTestNow(now()->addMinutes(16));

    expect($user->estVerrouille())->toBeFalse();
});

it('clears the counter and the lock after a successful login', function () {
    $user = new Utilisateur;
    foreach (range(1, 5) as $_) {
        $user->enregistrerEchec();
    }

    $user->reinitialiserEchecs();

    expect($user->tentatives_echouees)->toBe(0)
        ->and($user->verrouille_jusqua)->toBeNull()
        ->and($user->estVerrouille())->toBeFalse();
});
