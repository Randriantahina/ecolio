<?php

declare(strict_types=1);

namespace Modules\Scolarite\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: staff read the school calendar, only the directeur changes it; parents never see it. */
final class AnneeScolairePolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null
            && ($acteur->aRole(Role::Administration));
    }

    /** Filament's ability names; the same two spec roles decide them all. */
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
