<?php

declare(strict_types=1);

namespace Modules\Eleves\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: directeur and secretaire run the registry, enseignants read it, parents never. */
final class ElevePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Administration));
    }

    /** The abilities below are the ones Filament asks; they all reduce to the two spec roles of section 1. */
    public function view(Utilisateur $acteur): bool
    {
        return $this->viewAny($acteur);
    }

    public function create(Utilisateur $acteur): bool
    {
        return $this->manage($acteur);
    }

    public function update(Utilisateur $acteur): bool
    {
        return $this->manage($acteur);
    }

    public function manage(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && ($acteur->aRole(Role::Administration));
    }

    /** RG-E1 */
    public function corrigerMatricule(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Administration);
    }
}
