<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Finance\Filament\Resources\TypeFraisResource;

final class ListTypesFrais extends ListRecords
{
    protected static string $resource = TypeFraisResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau type de frais')];
    }
}
