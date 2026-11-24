<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Scolarite\Filament\Resources\AnneeScolaireResource;

final class ListAnneesScolaires extends ListRecords
{
    protected static string $resource = AnneeScolaireResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle année')];
    }
}
