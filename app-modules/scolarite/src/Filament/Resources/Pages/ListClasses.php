<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Scolarite\Filament\Resources\ClasseResource;

final class ListClasses extends ListRecords
{
    protected static string $resource = ClasseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle classe')];
    }
}
