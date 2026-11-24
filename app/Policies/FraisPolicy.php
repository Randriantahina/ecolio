<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/** Fee types and tariffs: the directeur and secretaire read them, only the directeur sets prices. */
final class FraisPolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && ($acteur->aRole(Role::Directeur) || $acteur->aRole(Role::Secretaire));
    }

    /** Filament's ability names; prices are set by the directeur only (spec section 1). */
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
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
