<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Resources\TarifResource;

final class EditTarif extends EditRecord
{
    protected static string $resource = TarifResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(FraisService::class)->modifierTarif(auth()->user(), $record->uuid, (int) $data['montant_ar']);
    }
}
