<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Filament\Resources\ChampPersonnaliseResource;

final class CreateChampPersonnalise extends CreateRecord
{
    protected static string $resource = ChampPersonnaliseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(ChampService::class)->creer(
                auth()->user(), EntiteChamp::from($data['entite']), $data['libelle'], TypeChamp::from($data['type']),
                $data['options'] ?? null, (bool) ($data['obligatoire'] ?? false),
            );
        } catch (ValidationException $e) {
            // Filament addresses form fields as data.<field>.
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn (array $m, string $k): array => ["data.$k" => $m])->all());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
