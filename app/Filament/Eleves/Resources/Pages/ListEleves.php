<?php

declare(strict_types=1);

namespace App\Filament\Eleves\Resources\Pages;

use App\Filament\Eleves\Resources\EleveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEleves extends ListRecords
{
    protected static string $resource = EleveResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvel élève')];
    }
}
