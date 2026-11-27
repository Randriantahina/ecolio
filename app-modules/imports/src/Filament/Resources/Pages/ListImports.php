<?php

declare(strict_types=1);

namespace Modules\Imports\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Imports\Filament\Resources\ImportResource;

final class ListImports extends ListRecords
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('nouveau')->label('Importer des élèves')->icon(Heroicon::OutlinedArrowUpTray)->url(ImportResource::urlNouvelImport())];
    }
}
