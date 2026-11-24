<?php

declare(strict_types=1);

namespace Modules\Absences\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Absences\Domain\Models\Absence;

final class AbsencesParJourChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Absences des 14 derniers jours';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Absence::class) ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $serie = app(AbsenceService::class)->parJour(auth()->user(), 14);

        return [
            'datasets' => [['label' => 'Absences', 'data' => array_values($serie), 'tension' => 0.3]],
            'labels' => array_map(fn (string $j): string => Carbon::parse($j)->format('d/m'), array_keys($serie)),
        ];
    }
}
