<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\Services\StructureService;
use App\Filament\Scolarite\Resources\ClasseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateClasse extends CreateRecord
{
    protected static string $resource = ClasseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StructureService::class)->creerClasse(auth()->user(), $data);
    }
}
