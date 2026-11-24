<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Eleves\Filament\Resources\EleveResource;

final class ListEleves extends ListRecords
{
    protected static string $resource = EleveResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvel élève')];
    }
}
