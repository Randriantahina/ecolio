<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Finance\Filament\Resources\TarifResource;

final class ListTarifs extends ListRecords
{
    protected static string $resource = TarifResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau tarif')];
    }
}
