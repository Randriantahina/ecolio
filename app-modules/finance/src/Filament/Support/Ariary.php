<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Support;

/** Display only: amounts are integer ariary everywhere else, never floats. */
final class Ariary
{
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' Ar';
    }
}
