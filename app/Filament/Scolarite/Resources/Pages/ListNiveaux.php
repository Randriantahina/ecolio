<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Filament\Scolarite\Resources\NiveauResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNiveaux extends ListRecords
{
    protected static string $resource = NiveauResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau niveau')];
    }
}
