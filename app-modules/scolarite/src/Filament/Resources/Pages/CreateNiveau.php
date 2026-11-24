<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Filament\Resources\NiveauResource;

final class CreateNiveau extends CreateRecord
{
    protected static string $resource = NiveauResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StructureService::class)->creerNiveau(auth()->user(), $data);
    }
}
