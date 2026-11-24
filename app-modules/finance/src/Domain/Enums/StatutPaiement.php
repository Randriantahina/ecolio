<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Enums;

enum StatutPaiement: string
{
    case Valide = 'valide';
    case Annule = 'annule';
}
