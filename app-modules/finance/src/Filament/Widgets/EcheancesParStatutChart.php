<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Finance\Application\Services\TableauDeBordFinanceService;
use Modules\Finance\Domain\Models\Paiement;

final class EcheancesParStatutChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'État des échéances';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Paiement::class) ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $r = app(TableauDeBordFinanceService::class)->repartitionEcheances(auth()->user());

        return [
            'datasets' => [['data' => array_values($r), 'backgroundColor' => ['#f59e0b', '#38bdf8', '#10b981', '#9ca3af']]],
            'labels' => ['Dues', 'Partielles', 'Payées', 'Annulées'],
        ];
    }
}
