<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Modules\Finance\Application\Services\TableauDeBordFinanceService;
use Modules\Finance\Domain\Models\Paiement;

final class EncaisseParMoisChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Encaissements des 6 derniers mois';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Paiement::class) ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $serie = app(TableauDeBordFinanceService::class)->encaisseParMois(auth()->user(), 6);

        return [
            'datasets' => [['label' => 'Ariary encaissés', 'data' => array_values($serie)]],
            'labels' => array_map(fn (string $m): string => Carbon::createFromFormat('Y-m', $m)->locale('fr')->translatedFormat('M Y'), array_keys($serie)),
        ];
    }
}
