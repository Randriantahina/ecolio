<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Dynamique\Domain\Enums\EntiteVue;
use Modules\Dynamique\Filament\Concerns\GereVues;
use Modules\Eleves\Filament\Resources\EleveResource;

final class ListEleves extends ListRecords
{
    use GereVues;

    protected static string $resource = EleveResource::class;

    protected function entiteVue(): EntiteVue
    {
        return EntiteVue::Eleve;
    }

    protected function getHeaderActions(): array
    {
        return [...$this->vuesActions(), CreateAction::make()->label('Nouvel élève')];
    }
}
