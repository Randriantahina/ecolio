<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Filament\Finance\Resources\TarifResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTarifs extends ListRecords
{
    protected static string $resource = TarifResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau tarif')];
    }
}
