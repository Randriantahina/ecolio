<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Enums;

enum Periodicite: string
{
    case Unique = 'unique';
    case Mensuel = 'mensuel';
    case Trimestriel = 'trimestriel';
    case Annuel = 'annuel';
}
