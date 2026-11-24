<?php

declare(strict_types=1);

use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;
use App\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = app(UtilisateurRepository::class);
});

it('is bound to the Eloquent implementation', function () {
    expect($this->repository)->toBeInstanceOf(EloquentUtilisateurRepository::class);
});

it('finds a user by e-mail', function () {
    $user = Utilisateur::factory()->create(['email' => 'a@b.mg']);

    expect($this->repository->findByLogin('a@b.mg')?->is($user))->toBeTrue();
});

it('finds a user by phone ignoring spaces on either side', function () {
    $user = Utilisateur::factory()->create(['email' => null, 'telephone' => '034 11 222 33']);

    expect($this->repository->findByLogin('0341122233')?->is($user))->toBeTrue()
        ->and($this->repository->findByLogin('034 11 222 33')?->is($user))->toBeTrue();
});

it('returns null for an unknown login', function () {
    expect($this->repository->findByLogin('nobody@nowhere.mg'))->toBeNull();
});
