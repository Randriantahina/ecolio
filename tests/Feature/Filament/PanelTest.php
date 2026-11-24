<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Domain\Enums\Role;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->autre = Ecole::factory()->create();
});

it('sends an anonymous visitor to the school login page', function () {
    $this->get("/ecole/{$this->ecole->slug}")->assertRedirect('/ecole/login');
    $this->get('/ecole/login')->assertOk();
});

it('lets school staff open the dashboard of their own school', function (Role $role) {
    $this->actingAs(utilisateurAvecRole($role, $this->ecole))
        ->get("/ecole/{$this->ecole->slug}")->assertOk();
})->with([Role::Directeur, Role::Secretaire, Role::Enseignant]);

it('keeps parents out of the staff panel', function () {
    $this->actingAs(utilisateurAvecRole(Role::Parent, $this->ecole))
        ->get("/ecole/{$this->ecole->slug}")->assertForbidden();
});

it('refuses the dashboard of another school (tenant isolation)', function () {
    $this->actingAs(utilisateurAvecRole(Role::Directeur, $this->ecole))
        ->get("/ecole/{$this->autre->slug}")->assertNotFound();
});

it('refuses a deactivated account', function () {
    $user = utilisateurAvecRole(Role::Directeur, $this->ecole);
    $user->update(['actif' => false]);

    $this->actingAs($user)->get("/ecole/{$this->ecole->slug}")->assertForbidden();
});
