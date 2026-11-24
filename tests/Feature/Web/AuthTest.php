<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create(['nom' => 'Collège Test']);
    $this->user = Utilisateur::factory()->for($this->ecole)->create([
        'nom' => 'Rasoa Directrice',
        'email' => 'directrice@ecole.mg',
        'mot_de_passe' => 'correct-horse',
    ]);
    (new RolesSeeder)->run();
    $this->user->assignRole(Role::Directeur->value);
});

it('shows the login page to a visitor', function () {
    $this->get('/connexion')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

it('sends an anonymous visitor to the login page', function () {
    $this->get('/')->assertRedirect('/connexion');
});

it('logs in with the session and lands on the home page', function () {
    $this->post('/connexion', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])->assertRedirect('/');

    $this->assertAuthenticatedAs($this->user);
});

it('refuses a wrong password with the same message as an unknown account', function () {
    $this->post('/connexion', ['login' => 'directrice@ecole.mg', 'password' => 'nope'])->assertSessionHasErrors('login');
    $this->post('/connexion', ['login' => 'inconnu@ecole.mg', 'password' => 'nope'])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('locks the account after repeated failures, even with the right password afterwards', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/connexion', ['login' => 'directrice@ecole.mg', 'password' => 'nope']);
    }

    $this->post('/connexion', ['login' => 'directrice@ecole.mg', 'password' => 'correct-horse'])->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('shares the signed-in user and school with every page', function () {
    $this->actingAs($this->user)->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Accueil')
        ->where('auth.utilisateur.nom', 'Rasoa Directrice')
        ->where('auth.utilisateur.ecole.nom', 'Collège Test')
        ->where('auth.utilisateur.roles', ['directeur']));
});

it('logs out and forgets the session', function () {
    $this->actingAs($this->user)->post('/deconnexion')->assertRedirect('/connexion');

    $this->assertGuest();
});

it('keeps a signed-in user away from the login page', function () {
    $this->actingAs($this->user)->get('/connexion')->assertRedirect('/');
});
