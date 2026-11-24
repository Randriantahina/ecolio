<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Finance\Application\Services\TableauDeBordFinanceService;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Filament\Support\Ariary;

final class FinanceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Paiement::class) ?? false;
    }

    protected function getStats(): array
    {
        $t = app(TableauDeBordFinanceService::class)->pour(auth()->user());

        return [
            Stat::make('Encaissé ce mois', Ariary::format($t['encaisse_mois_ar'])),
            Stat::make('Reste à payer', Ariary::format($t['reste_a_payer_ar']))
                ->description($t['echeances_impayees'].' échéance(s) non soldée(s)')
                ->color($t['echeances_impayees'] > 0 ? 'warning' : 'success'),
        ];
    }
}
