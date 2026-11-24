<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Filament\Resources\ChampPersonnaliseResource;

final class EditChampPersonnalise extends EditRecord
{
    protected static string $resource = ChampPersonnaliseResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $champs = app(ChampService::class);
        $type = TypeChamp::from($data['type']);
        $options = $data['options'] ?? null;

        try {
            if ($type !== $record->type || ($type === TypeChamp::Liste && $options !== $record->options)) {
                $champs->changerType(auth()->user(), $record->uuid, $type, $options);
            }
            $champs->renommer(auth()->user(), $record->uuid, $data['libelle']);

            return $champs->definirObligatoire(auth()->user(), $record->uuid, (bool) ($data['obligatoire'] ?? false));
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn (array $m, string $k): array => ["data.$k" => $m])->all());
        }
    }
}
