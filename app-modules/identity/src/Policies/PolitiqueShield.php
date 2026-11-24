<?php

declare(strict_types=1);

namespace Modules\Identity\Policies;

use Modules\Identity\Domain\Models\Utilisateur;

/**
 * Authorisation by Filament Shield permissions ("Action:Sujet", edited on the Roles page) instead of
 * hard-coded roles, so the administration can change what each role may do (decision 0006).
 *
 * `checkPermissionTo` (not `can`) on purpose: `can` would loop back into the Gate and this policy,
 * and `hasPermissionTo` throws when the permission row does not exist yet.
 */
abstract class PolitiqueShield
{
    /** Subject as Shield names it: the model's base name, e.g. "Eleve". */
    abstract protected function sujet(): string;

    public function viewAny(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'ViewAny');
    }

    public function view(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'View');
    }

    public function create(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'Create');
    }

    public function update(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'Update');
    }

    public function delete(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'Delete');
    }

    /** Write access in the services and API requests: whoever may update may manage. */
    public function manage(Utilisateur $acteur): bool
    {
        return $this->update($acteur);
    }

    protected function permis(Utilisateur $acteur, string $action): bool
    {
        return $acteur->ecole_id !== null && $acteur->checkPermissionTo("{$action}:{$this->sujet()}");
    }
}
