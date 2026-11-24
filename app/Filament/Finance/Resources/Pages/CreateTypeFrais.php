<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\Pages;

use App\Application\Finance\Services\FraisService;
use App\Filament\Finance\Resources\TypeFraisResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTypeFrais extends CreateRecord
{
    protected static string $resource = TypeFraisResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(FraisService::class)->creerType(auth()->user(), $data);
    }
}
