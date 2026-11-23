<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/** Spec section 1: staff read the school calendar, only the directeur changes it; parents never see it. */
final class AnneeScolairePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Directeur) || $acteur->aRole(Role::Secretaire) || $acteur->aRole(Role::Enseignant));
    }

    public function manage(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
