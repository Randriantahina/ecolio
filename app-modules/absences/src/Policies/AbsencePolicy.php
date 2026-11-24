<?php

declare(strict_types=1);

namespace Modules\Absences\Policies;

use Modules\Identity\Policies\PolitiqueShield;

final class AbsencePolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Absence';
    }
}
