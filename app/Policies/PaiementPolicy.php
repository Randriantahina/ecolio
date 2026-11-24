<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/** Spec section 1: the directeur and the secretaire handle money; only the directeur voids (RG-P7). */
final class PaiementPolicy
{
    public function viewAny(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && ($acteur->aRole(Role::Directeur) || $acteur->aRole(Role::Secretaire));
    }

    public function create(Utilisateur $acteur): bool
    {
        return $this->viewAny($acteur);
    }

    /** [Hypothèse] the secretaire voiding with the directeur's validation is not built yet. */
    public function annuler(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
