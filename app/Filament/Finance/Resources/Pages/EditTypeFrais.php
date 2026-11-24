<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Application\Finance\Services\FraisService;
use App\Filament\Finance\Resources\TypeFraisResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditTypeFrais extends EditRecord
{
    protected static string $resource = TypeFraisResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(FraisService::class)->modifierType(auth()->user(), $record->uuid, $data);
    }
}
