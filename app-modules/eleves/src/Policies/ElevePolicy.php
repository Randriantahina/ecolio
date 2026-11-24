<?php

declare(strict_types=1);

namespace Modules\Eleves\Policies;

use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Policies\PolitiqueShield;

final class ElevePolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Eleve';
    }

    /** RG-E1: correcting a matricule is a rare, audited act: it needs the right to update pupils. */
    public function corrigerMatricule(Utilisateur $acteur): bool
    {
        return $this->update($acteur);
    }
}
