<?php

declare(strict_types=1);

namespace Modules\Imports\Policies;

use Modules\Identity\Policies\PolitiqueShield;

/** Imports have no resource of their own: their keys are Shield custom permissions (config/filament-shield.php). */
final class ImportPolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Import';
    }
}
