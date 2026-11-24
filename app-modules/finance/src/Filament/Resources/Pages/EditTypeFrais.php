<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Resources\TypeFraisResource;

final class EditTypeFrais extends EditRecord
{
    protected static string $resource = TypeFraisResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(FraisService::class)->modifierType(auth()->user(), $record->uuid, $data);
    }
}
