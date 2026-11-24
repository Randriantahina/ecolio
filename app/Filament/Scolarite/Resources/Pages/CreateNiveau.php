<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\Services\StructureService;
use App\Filament\Scolarite\Resources\NiveauResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateNiveau extends CreateRecord
{
    protected static string $resource = NiveauResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StructureService::class)->creerNiveau(auth()->user(), $data);
    }
}
