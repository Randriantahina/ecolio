<?php

declare(strict_types=1);

namespace Modules\Identity\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: only the directeur manages users and settings of their school. */
final class UtilisateurPolicy
{
    public function gererEquipe(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
