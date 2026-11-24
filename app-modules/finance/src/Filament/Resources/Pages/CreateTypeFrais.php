<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Filament\Resources\TypeFraisResource;

final class CreateTypeFrais extends CreateRecord
{
    protected static string $resource = TypeFraisResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(FraisService::class)->creerType(auth()->user(), $data);
    }
}
