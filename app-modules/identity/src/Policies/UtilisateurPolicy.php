<?php

declare(strict_types=1);

namespace Modules\Identity\Policies;

use Modules\Identity\Domain\Models\Utilisateur;

final class UtilisateurPolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Utilisateur';
    }

    /** Inviting and managing the school team. */
    public function gererEquipe(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'Create');
    }
}
