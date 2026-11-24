<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Domain\Models\Eleve;

final class EffectifsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Eleve::class) ?? false;
    }

    protected function getStats(): array
    {
        $e = app(EleveService::class)->effectifs(auth()->user());

        return [
            Stat::make('Élèves actifs', (string) $e['actifs']),
            Stat::make('Sans classe', (string) $e['sans_classe'])
                ->description('À inscrire dans une classe')
                ->color($e['sans_classe'] > 0 ? 'warning' : 'success'),
        ];
    }
}
