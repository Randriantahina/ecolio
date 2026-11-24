<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Domain\Contracts\UtilisateurRepository;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;

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
