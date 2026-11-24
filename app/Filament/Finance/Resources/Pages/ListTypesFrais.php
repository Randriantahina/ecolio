<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Filament\Finance\Resources\TypeFraisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTypesFrais extends ListRecords
{
    protected static string $resource = TypeFraisResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau type de frais')];
    }
}
