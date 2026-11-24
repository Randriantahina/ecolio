<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Enums;

enum Periodicite: string
{
    case Unique = 'unique';
    case Mensuel = 'mensuel';
    case Trimestriel = 'trimestriel';
    case Annuel = 'annuel';

    /** One-off fees (registration, annual dues) have no month: the register lists them apart from the tuition. */
    public function estPonctuelle(): bool
    {
        return $this === self::Unique || $this === self::Annuel;
    }
}
