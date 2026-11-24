<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Filament\Resources\ClasseResource;

final class EditClasse extends EditRecord
{
    protected static string $resource = ClasseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StructureService::class)->modifierClasse(auth()->user(), $record->uuid, $data);
    }
}
