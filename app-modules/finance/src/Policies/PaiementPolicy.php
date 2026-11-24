<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Policies\PolitiqueShield;

/** Payments have no Filament resource, so their keys are Shield custom permissions (config/filament-shield.php). */
final class PaiementPolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Paiement';
    }

    /** RG-P7: voiding is its own right, kept apart from collecting. */
    public function annuler(Utilisateur $acteur): bool
    {
        return $this->permis($acteur, 'Delete');
    }
}
