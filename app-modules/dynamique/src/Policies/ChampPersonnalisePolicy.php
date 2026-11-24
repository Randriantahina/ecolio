<?php

declare(strict_types=1);

namespace Modules\Dynamique\Policies;

use Modules\Identity\Policies\PolitiqueShield;

final class ChampPersonnalisePolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'ChampPersonnalise';
    }
}
