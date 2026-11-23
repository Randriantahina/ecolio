<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum StatutPaiement: string
{
    case Valide = 'valide';
    case Annule = 'annule';
}
