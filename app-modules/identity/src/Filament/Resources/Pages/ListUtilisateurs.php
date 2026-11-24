<?php

declare(strict_types=1);

namespace Modules\Identity\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Identity\Filament\Resources\UtilisateurResource;

final class ListUtilisateurs extends ListRecords
{
    protected static string $resource = UtilisateurResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Inviter un utilisateur')];
    }
}
