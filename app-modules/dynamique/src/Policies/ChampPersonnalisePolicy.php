<?php

declare(strict_types=1);

namespace Modules\Dynamique\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: the directeur manages the columns, staff only read them. */
final class ChampPersonnalisePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Administration));
    }

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

    public function delete(Utilisateur $acteur): bool
    {
        return $this->manage($acteur);
    }

    public function manage(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Administration);
    }
}
