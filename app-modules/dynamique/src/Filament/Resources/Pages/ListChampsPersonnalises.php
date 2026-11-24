<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Dynamique\Filament\Resources\ChampPersonnaliseResource;

final class ListChampsPersonnalises extends ListRecords
{
    protected static string $resource = ChampPersonnaliseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajouter un champ')];
    }
}
