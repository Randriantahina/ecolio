<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Enums;

enum ModePaiement: string
{
    case Especes = 'especes';
    case Mvola = 'mvola';
    case OrangeMoney = 'orange_money';
    case AirtelMoney = 'airtel_money';
    case Virement = 'virement';
    case Cheque = 'cheque';
}
