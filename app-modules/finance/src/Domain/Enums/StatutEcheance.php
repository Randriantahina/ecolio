<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Enums;

enum StatutEcheance: string
{
    case Du = 'du';
    case Partiel = 'partiel';
    case Paye = 'paye';
    case Annule = 'annule';
}
