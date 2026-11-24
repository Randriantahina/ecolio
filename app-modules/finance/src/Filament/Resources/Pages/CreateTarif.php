<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Resources\TarifResource;

final class CreateTarif extends CreateRecord
{
    protected static string $resource = TarifResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(FraisService::class)->creerTarif(auth()->user(), $data);
    }
}
