<?php

declare(strict_types=1);

namespace Modules\Absences\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: staff take attendance. TODO(phase-3): restrict enseignants to their own classes. */
final class AbsencePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Administration) || $acteur->aRole(Role::Enseignant));
    }

    public function manage(Utilisateur $acteur): bool
    {
        return $this->viewAny($acteur);
    }
}
