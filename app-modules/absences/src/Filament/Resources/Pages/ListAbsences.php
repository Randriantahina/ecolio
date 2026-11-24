<?php

declare(strict_types=1);

namespace Modules\Absences\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Absences\Filament\Pages\AppelDuJour;
use Modules\Absences\Filament\Resources\AbsenceResource;

final class ListAbsences extends ListRecords
{
    protected static string $resource = AbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('appel')->label('Faire l\'appel')->url(fn (): string => AppelDuJour::getUrl())];
    }
}
