<?php

declare(strict_types=1);

namespace Modules\Platform\Policies;

use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;

/** Spec section 1: settings belong to the directeur; other school roles only read. */
final class EcolePolicy
{
    public function modifier(Utilisateur $acteur): bool
    {
        return $acteur->ecole_id !== null && $acteur->aRole(Role::Administration);
    }
}
