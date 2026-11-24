<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Application\Services\InvitationService;
use Modules\Identity\Domain\Contracts\EnvoyeurSms;
use Modules\Identity\Domain\Enums\CanalInvitation;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Infrastructure\Mail\InvitationMail;
use Modules\Platform\Domain\Models\Ecole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Ecole::factory()->create(['nom' => 'Collège Demo']);
    $this->admin = utilisateurAvecRole(Role::Administration, $this->ecole);
    $this->sms = new class implements EnvoyeurSms
    {
        /** @var list<array{0: string, 1: string}> */
        public array $envoyes = [];

        public function envoyer(string $numero, string $message): void
        {
            $this->envoyes[] = [$numero, $message];
        }
    };
    app()->instance(EnvoyeurSms::class, $this->sms);
    Mail::fake();
});

it('invites by email: the account is created in the school with the role and the mail carries a temporary password', function () {
    $membre = app(InvitationService::class)->inviter(
        $this->admin, 'Rasoa', 'rasoa@ecole.mg', null, Role::Enseignant, CanalInvitation::Email, 'https://ecolio.test/ecole/login',
    );

    expect($membre->ecole_id)->toBe($this->ecole->id)->and($membre->aRole(Role::Enseignant))->toBeTrue();

    Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($membre) {
        return $mail->hasTo('rasoa@ecole.mg') && Hash::check($mail->motDePasse, $membre->mot_de_passe)
            && str_contains($mail->render(), 'Collège Demo') && str_contains($mail->render(), 'https://ecolio.test/ecole/login');
    });
    expect($this->sms->envoyes)->toBe([]);
});

it('invites by SMS: the message goes to the phone number and holds the login and temporary password', function () {
    $membre = app(InvitationService::class)->inviter(
        $this->admin, 'Hery', null, '034 11 222 33', Role::Enseignant, CanalInvitation::Sms, 'https://ecolio.test/ecole/login',
    );

    expect($this->sms->envoyes)->toHaveCount(1)->and($this->sms->envoyes[0][0])->toBe('034 11 222 33')
        ->and($this->sms->envoyes[0][1])->toContain('034 11 222 33')->toContain('https://ecolio.test/ecole/login');
    Mail::assertNothingSent();
    expect($membre->telephone)->toBe('034 11 222 33');
});

it('requires an email for the email channel and a phone for the SMS channel', function () {
    $service = app(InvitationService::class);

    expect(fn () => $service->inviter($this->admin, 'X', null, '034', Role::Enseignant, CanalInvitation::Email, 'u'))
        ->toThrow(ValidationException::class)
        ->and(fn () => $service->inviter($this->admin, 'X', 'x@ecole.mg', null, Role::Enseignant, CanalInvitation::Sms, 'u'))
        ->toThrow(ValidationException::class);
});

it('never creates a super admin or a stray account when the invitation is refused', function () {
    $service = app(InvitationService::class);

    expect(fn () => $service->inviter($this->admin, 'X', 'x@ecole.mg', null, Role::SuperAdmin, CanalInvitation::Email, 'u'))
        ->toThrow(ValidationException::class);
    expect(Utilisateur::where('email', 'x@ecole.mg')->exists())->toBeFalse();
});

it('is refused to an enseignant', function () {
    $prof = utilisateurAvecRole(Role::Enseignant, $this->ecole);

    expect(fn () => app(InvitationService::class)->inviter($prof, 'X', 'x@ecole.mg', null, Role::Enseignant, CanalInvitation::Email, 'u'))
        ->toThrow(AuthorizationException::class);
});
