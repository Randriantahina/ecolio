<?php

declare(strict_types=1);

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Domain\Models\Ecole;

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

it('can run twice without duplicating the school, accounts, pupils or payments', function () {
    $this->app['env'] = 'local';

    $this->seed(DemoSeeder::class);
    $paiements = Paiement::withoutGlobalScopes()->count();
    $this->seed(DemoSeeder::class);

    expect(Ecole::count())->toBe(1)
        ->and(Utilisateur::count())->toBe(3)
        ->and(Eleve::withoutGlobalScopes()->count())->toBe(24)
        ->and(Paiement::withoutGlobalScopes()->count())->toBe($paiements);
});

it('refuses to run outside local so a known password never reaches production', function () {
    $this->app['env'] = 'production';

    // Called directly: `db:seed` already refuses production, this proves the seeder's own guard too.
    (new DemoSeeder)->run();
})->throws(RuntimeException::class);

it('shows every box state in the demo register: paid, partial, due and a voided receipt', function () {
    $this->app['env'] = 'local';
    $this->seed(DemoSeeder::class);

    $statuts = Echeance::withoutGlobalScopes()->pluck('statut')->map->value->unique()->all();

    expect($statuts)->toContain('paye', 'partiel', 'du')
        ->and(Paiement::withoutGlobalScopes()->where('statut', 'annule')->count())->toBe(1);
});
