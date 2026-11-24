<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Filament\Resources\NiveauResource;

final class EditNiveau extends EditRecord
{
    protected static string $resource = NiveauResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StructureService::class)->modifierNiveau(auth()->user(), $record->uuid, $data);
    }
}
