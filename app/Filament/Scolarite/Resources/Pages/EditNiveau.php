<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\Services\StructureService;
use App\Filament\Scolarite\Resources\NiveauResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditNiveau extends EditRecord
{
    protected static string $resource = NiveauResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StructureService::class)->modifierNiveau(auth()->user(), $record->uuid, $data);
    }
}
