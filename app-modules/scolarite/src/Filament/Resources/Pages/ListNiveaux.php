<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Scolarite\Filament\Resources\NiveauResource;

final class ListNiveaux extends ListRecords
{
    protected static string $resource = NiveauResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau niveau')];
    }
}
