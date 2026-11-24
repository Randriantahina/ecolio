<?php

declare(strict_types=1);

namespace Modules\Identity\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Identity\Application\DTOs\UpdateUtilisateurDto;
use Modules\Identity\Application\Services\TeamService;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Filament\Resources\UtilisateurResource;

final class EditUtilisateur extends EditRecord
{
    protected static string $resource = UtilisateurResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->roles->first()?->name;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(TeamService::class)->modifier(auth()->user(), $record->uuid, new UpdateUtilisateurDto(
            nom: $data['nom'], actif: (bool) $data['actif'], role: Role::from($data['role']),
        ));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
