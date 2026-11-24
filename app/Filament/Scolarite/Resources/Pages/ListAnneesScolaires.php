<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Filament\Scolarite\Resources\AnneeScolaireResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAnneesScolaires extends ListRecords
{
    protected static string $resource = AnneeScolaireResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle année')];
    }
}
