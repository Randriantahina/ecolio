<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/** Spec section 1: directeur and secretaire run the registry, enseignants read it, parents never. */
final class ElevePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Directeur) || $acteur->aRole(Role::Secretaire) || $acteur->aRole(Role::Enseignant));
    }

    public function manage(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && ($acteur->aRole(Role::Directeur) || $acteur->aRole(Role::Secretaire));
    }

    /** RG-E1 */
    public function corrigerMatricule(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
