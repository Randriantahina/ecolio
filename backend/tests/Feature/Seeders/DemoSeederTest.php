<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a fictional school with a directeur who can log in', function () {
    $this->app['env'] = 'local';

    $this->seed(DemoSeeder::class);

    $directeur = Utilisateur::where('email', 'directeur@demo.ecolio.test')->firstOrFail();
    expect($directeur->aRole(Role::Directeur))->toBeTrue()
        ->and($directeur->ecole->nom)->toBe('École Démo (données fictives)');

    $this->postJson('/api/v1/auth/login', ['login' => 'directeur@demo.ecolio.test', 'password' => 'password'])
        ->assertOk();
});

it('can run twice without duplicating the school or the account', function () {
    $this->app['env'] = 'local';

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Ecole::count())->toBe(1)
        ->and(Utilisateur::count())->toBe(1);
});

it('refuses to run outside local so a known password never reaches production', function () {
    $this->app['env'] = 'production';

    // Called directly: `db:seed` already refuses production, this proves the seeder's own guard too.
    (new DemoSeeder)->run();
})->throws(RuntimeException::class);
