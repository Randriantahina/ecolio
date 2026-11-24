<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Application\Finance\Services\FraisService;
use App\Filament\Finance\Resources\TarifResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTarif extends CreateRecord
{
    protected static string $resource = TarifResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(FraisService::class)->creerTarif(auth()->user(), $data);
    }
}
