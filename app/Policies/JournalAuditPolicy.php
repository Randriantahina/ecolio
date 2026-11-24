<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\Utilisateur;

/** Spec section 1: the audit trail is read-only and reserved to the directeur. */
final class JournalAuditPolicy
{
    public function voir(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Directeur);
    }
}
