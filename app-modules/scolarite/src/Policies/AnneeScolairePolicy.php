<?php

declare(strict_types=1);

namespace Modules\Scolarite\Policies;

use Modules\Identity\Policies\PolitiqueShield;

final class AnneeScolairePolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'AnneeScolaire';
    }
}
