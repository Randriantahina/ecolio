<?php

declare(strict_types=1);

namespace Modules\Finance\Policies;

use Modules\Identity\Policies\PolitiqueShield;

final class TarifPolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Tarif';
    }
}
