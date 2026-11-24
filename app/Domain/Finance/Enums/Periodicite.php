<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum Periodicite: string
{
    case Unique = 'unique';
    case Mensuel = 'mensuel';
    case Trimestriel = 'trimestriel';
    case Annuel = 'annuel';
}
