<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Filament\Resources\ClasseResource;

final class CreateClasse extends CreateRecord
{
    protected static string $resource = ClasseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StructureService::class)->creerClasse(auth()->user(), $data);
    }
}
