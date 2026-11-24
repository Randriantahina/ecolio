<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Database\Seeders\RolesSeeder;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Domain\TenantContext;

uses(RefreshDatabase::class);

it('rejects /me without a token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('returns the authenticated profile with role and school', function () {
    $this->seed(RolesSeeder::class);
    $ecole = Ecole::factory()->create(['nom' => 'Ecole Pilote']);
    $user = Utilisateur::factory()->for($ecole)->create();
    $user->assignRole(Role::Administration->value);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('utilisateur.uuid', $user->uuid)
        ->assertJsonPath('utilisateur.roles', ['administration'])
        ->assertJsonPath('utilisateur.ecole.nom', 'Ecole Pilote');
});

it('never exposes the password hash or internal ids', function () {
    $user = Utilisateur::factory()->for(Ecole::factory())->create();
    Sanctum::actingAs($user);

    $body = $this->getJson('/api/v1/me')->json('utilisateur');

    expect($body)->not->toHaveKeys(['mot_de_passe', 'id', 'ecole_id']);
});

it('revokes only the current token on logout', function () {
    $user = Utilisateur::factory()->for(Ecole::factory())->create();
    $current = $user->createToken('phone')->plainTextToken;
    $user->createToken('desktop');

    $this->withToken($current)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->first()->name)->toBe('desktop');
});

describe('tenant resolution', function () {
    beforeEach(function () {
        Route::middleware(['auth:sanctum', 'tenant'])->get('/api/v1/_probe', fn () => response()->json([
            'ecole_id' => app(TenantContext::class)->ecoleId(),
            'platform' => app(TenantContext::class)->isPlatform(),
        ]));
    });

    it('binds the school of the authenticated user', function () {
        $ecole = Ecole::factory()->create();
        Sanctum::actingAs(Utilisateur::factory()->for($ecole)->create());

        $this->getJson('/api/v1/_probe')->assertOk()->assertExactJson(['ecole_id' => $ecole->id, 'platform' => false]);
    });

    it('puts a super admin in platform mode', function () {
        Sanctum::actingAs(Utilisateur::factory()->superAdmin()->create());

        $this->getJson('/api/v1/_probe')->assertOk()->assertExactJson(['ecole_id' => null, 'platform' => true]);
    });
});
