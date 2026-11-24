<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Support;

use Modules\Finance\Domain\Enums\ModePaiement;

final class ModePaiementLabels
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            ModePaiement::Especes->value => 'Espèces',
            ModePaiement::Mvola->value => 'MVola',
            ModePaiement::OrangeMoney->value => 'Orange Money',
            ModePaiement::AirtelMoney->value => 'Airtel Money',
            ModePaiement::Virement->value => 'Virement',
            ModePaiement::Cheque->value => 'Chèque',
        ];
    }
}
