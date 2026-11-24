<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\Services\StructureService;
use App\Filament\Scolarite\Resources\ClasseResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditClasse extends EditRecord
{
    protected static string $resource = ClasseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StructureService::class)->modifierClasse(auth()->user(), $record->uuid, $data);
    }
}
