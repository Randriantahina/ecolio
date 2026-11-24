<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Application\DTOs\CreateUtilisateurDto;
use Modules\Identity\Domain\Contracts\EnvoyeurSms;
use Modules\Identity\Domain\Enums\CanalInvitation;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Infrastructure\Mail\InvitationMail;

/** Creates a team member and tells them how to log in, by the channel the administration picked. */
final class InvitationService
{
    public function __construct(private readonly TeamService $equipe, private readonly EnvoyeurSms $sms) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function inviter(
        Utilisateur $acteur, string $nom, ?string $email, ?string $telephone, Role $role, CanalInvitation $canal, string $urlConnexion,
    ): Utilisateur {
        Gate::forUser($acteur)->authorize('gererEquipe', Utilisateur::class);

        $this->verifier($email, $telephone, $role, $canal);

        // The person never chooses it for themselves: a random provisional password they are told to change.
        $motDePasse = Str::password(10, symbols: false);
        $membre = $this->equipe->creer($acteur, new CreateUtilisateurDto($nom, $email, $telephone, $motDePasse, $role));

        $identifiant = $canal === CanalInvitation::Email ? (string) $email : (string) $telephone;
        $nomEcole = (string) $acteur->ecole?->nom;

        if ($canal === CanalInvitation::Email) {
            Mail::to($email)->send(new InvitationMail($nomEcole, $nom, $identifiant, $motDePasse, $urlConnexion));
        } else {
            $this->sms->envoyer((string) $telephone, "$nomEcole : connexion $urlConnexion – identifiant $identifiant – mot de passe provisoire $motDePasse");
        }

        return $membre;
    }

    private function verifier(?string $email, ?string $telephone, Role $role, CanalInvitation $canal): void
    {
        $erreurs = [];
        if ($role === Role::SuperAdmin) {
            $erreurs['role'] = 'Ce rôle ne peut pas être attribué ici.';
        }
        if ($canal === CanalInvitation::Email && blank($email)) {
            $erreurs['email'] = "L'email est obligatoire pour une invitation par email.";
        }
        if ($canal === CanalInvitation::Sms && blank($telephone)) {
            $erreurs['telephone'] = 'Le téléphone est obligatoire pour une invitation par SMS.';
        }
        if ($erreurs !== []) {
            throw ValidationException::withMessages($erreurs);
        }
    }
}
