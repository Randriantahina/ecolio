<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Application\Finance\Services\FraisService;
use App\Filament\Finance\Resources\TarifResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditTarif extends EditRecord
{
    protected static string $resource = TarifResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(FraisService::class)->modifierTarif(auth()->user(), $record->uuid, (int) $data['montant_ar']);
    }
}
