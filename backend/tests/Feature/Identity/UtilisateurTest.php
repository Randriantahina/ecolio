<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('stores a hashed password and a public uuid', function () {
    $user = Utilisateur::factory()->create(['mot_de_passe' => 'secret-pass']);

    expect($user->mot_de_passe)->not->toBe('secret-pass')
        ->and(Hash::check('secret-pass', $user->mot_de_passe))->toBeTrue()
        ->and($user->uuid)->not->toBeEmpty();
});

it('belongs to one school', function () {
    $ecole = Ecole::factory()->create();
    $user = Utilisateur::factory()->for($ecole)->create();

    expect($user->ecole->is($ecole))->toBeTrue()
        ->and($user->estSuperAdmin())->toBeFalse();
});

it('is a platform super admin when it has no school', function () {
    $user = Utilisateur::factory()->superAdmin()->create();

    expect($user->ecole_id)->toBeNull()
        ->and($user->estSuperAdmin())->toBeTrue();
});

it('refuses a user with neither e-mail nor phone', function () {
    Utilisateur::factory()->create(['email' => null, 'telephone' => null]);
})->throws(InvalidArgumentException::class);

it('accepts a phone-only user so staff without e-mail can log in (US1.1)', function () {
    $user = Utilisateur::factory()->create(['email' => null, 'telephone' => '034 11 222 33']);

    expect($user->exists)->toBeTrue();
});

it('seeds the five roles of the spec', function () {
    $this->seed(RolesSeeder::class);

    $names = Spatie\Permission\Models\Role::pluck('name')->sort()->values()->all();

    expect($names)->toBe(['directeur', 'enseignant', 'parent', 'secretaire', 'super_admin']);
});

it('is idempotent so deploys can reseed safely', function () {
    $this->seed(RolesSeeder::class);
    $this->seed(RolesSeeder::class);

    expect(Spatie\Permission\Models\Role::count())->toBe(5);
});

it('assigns and checks a role through the enum', function () {
    $this->seed(RolesSeeder::class);
    $user = Utilisateur::factory()->for(Ecole::factory())->create();

    $user->assignRole(Role::Directeur->value);

    expect($user->aRole(Role::Directeur))->toBeTrue()
        ->and($user->aRole(Role::Secretaire))->toBeFalse();
});
