<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Filament\Resources\Pages\CreateUtilisateur;
use Modules\Identity\Filament\Resources\Pages\EditUtilisateur;
use Modules\Identity\Filament\Resources\Pages\ListUtilisateurs;
use Modules\Identity\Filament\Resources\RoleResource;
use Modules\Identity\Filament\Resources\UtilisateurResource;
use Modules\Identity\Infrastructure\Mail\InvitationMail;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create();
    $this->admin = utilisateurAvecRole(Role::Administration, $this->ecole);
    dansLePanelEcole($this->admin, $this->ecole);
});

it('lists the team of the school with roles', function () {
    $prof = utilisateurAvecRole(Role::Enseignant, $this->ecole);

    Livewire::test(ListUtilisateurs::class)->assertCanSeeTableRecords(Utilisateur::query()->get())->assertSee($prof->nom);
});

it('invites a teacher by email from the form', function () {
    Mail::fake();

    Livewire::test(CreateUtilisateur::class)
        ->fillForm(['nom' => 'Rasoa', 'role' => 'enseignant', 'canal' => 'email', 'email' => 'rasoa@ecole.mg'])
        ->call('create')->assertHasNoFormErrors()->assertNotified('Invitation envoyée');

    Mail::assertSent(InvitationMail::class);
    expect(Utilisateur::where('email', 'rasoa@ecole.mg')->firstOrFail()->aRole(Role::Enseignant))->toBeTrue();
});

it('asks for a phone number when the SMS channel is chosen', function () {
    Livewire::test(CreateUtilisateur::class)
        ->fillForm(['nom' => 'Hery', 'role' => 'enseignant', 'canal' => 'sms', 'telephone' => null])
        ->call('create')->assertHasFormErrors(['telephone' => 'required']);
});

it('offers no way to create a super admin from the form', function () {
    expect(UtilisateurResource::rolesAssignables())->not->toHaveKey('super_admin');
});

it('changes the role and deactivates a member', function () {
    $prof = utilisateurAvecRole(Role::Enseignant, $this->ecole);

    Livewire::test(EditUtilisateur::class, ['record' => $prof->uuid])
        ->fillForm(['role' => 'administration', 'actif' => false])->call('save')->assertHasNoFormErrors();

    expect($prof->fresh()->aRole(Role::Administration))->toBeTrue()->and($prof->fresh()->actif)->toBeFalse();
});

it('shows the users and roles pages to the administration only', function () {
    expect(UtilisateurResource::canViewAny())->toBeTrue()->and(RoleResource::canViewAny())->toBeTrue();

    dansLePanelEcole(utilisateurAvecRole(Role::Enseignant, $this->ecole), $this->ecole);
    expect(UtilisateurResource::canViewAny())->toBeFalse()->and(RoleResource::canViewAny())->toBeFalse();
});

it('gives the enseignant role the absence permissions and nothing else', function () {
    $prof = utilisateurAvecRole(Role::Enseignant, $this->ecole);

    expect($prof->checkPermissionTo('ViewAny:Absence'))->toBeTrue()
        ->and($prof->checkPermissionTo('ViewAny:Eleve'))->toBeFalse()
        ->and($prof->checkPermissionTo('ViewAny:Paiement'))->toBeFalse()
        ->and($prof->checkPermissionTo('ViewAny:Utilisateur'))->toBeFalse();
});

it('never lists the users of another school (tenant isolation)', function () {
    $autre = Ecole::factory()->create();
    $etranger = utilisateurAvecRole(Role::Administration, $autre);

    Livewire::test(ListUtilisateurs::class)->assertCanNotSeeTableRecords(collect([$etranger]));
});

it('serves the users and roles pages inside the settings cluster', function () {
    $this->get("/ecole/{$this->ecole->slug}/parametres/utilisateurs")->assertOk();
    $this->get("/ecole/{$this->ecole->slug}/parametres/shield/roles")->assertOk();
});
