<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Models\Ecole;
use App\Filament\Identity\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->user = utilisateurAvecRole(Role::Directeur, $this->ecole);
    $this->user->update(['email' => 'dir@ecole.test', 'mot_de_passe' => 'secret-pass']);
    Filament\Facades\Filament::setCurrentPanel('ecole');
});

it('signs a directeur in with e-mail and password', function () {
    Livewire::test(Login::class)
        ->fillForm(['login' => 'dir@ecole.test', 'password' => 'secret-pass'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(auth()->id())->toBe($this->user->id)
        ->and($this->user->refresh()->derniere_connexion)->not->toBeNull();
});

it('gives the same error for a wrong password and an unknown account', function () {
    $wrong = Livewire::test(Login::class)->fillForm(['login' => 'dir@ecole.test', 'password' => 'nope'])->call('authenticate');
    $unknown = Livewire::test(Login::class)->fillForm(['login' => 'ghost@ecole.test', 'password' => 'nope'])->call('authenticate');

    $wrong->assertHasFormErrors(['login']);
    $unknown->assertHasFormErrors(['login']);
    expect(auth()->check())->toBeFalse();
});

it('locks the account after repeated failures even with the right password (RG-A3)', function () {
    foreach (range(1, Utilisateur::MAX_ECHECS) as $_) {
        Livewire::test(Login::class)->fillForm(['login' => 'dir@ecole.test', 'password' => 'bad'])->call('authenticate');
    }

    Livewire::test(Login::class)
        ->fillForm(['login' => 'dir@ecole.test', 'password' => 'secret-pass'])
        ->call('authenticate')
        ->assertHasFormErrors(['login']);

    expect(auth()->check())->toBeFalse();
});

it('does not let a parent in through the staff login', function () {
    $parent = utilisateurAvecRole(Role::Parent, $this->ecole);
    $parent->update(['email' => 'parent@ecole.test', 'mot_de_passe' => 'secret-pass']);

    Livewire::test(Login::class)
        ->fillForm(['login' => 'parent@ecole.test', 'password' => 'secret-pass'])
        ->call('authenticate')
        ->assertHasFormErrors(['login']);

    expect(auth()->check())->toBeFalse();
});
