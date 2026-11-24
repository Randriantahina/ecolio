<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\DTOs\UpdateAnneeScolaireDto;
use App\Application\Scolarite\Services\AnneeScolaireService;
use App\Domain\Scolarite\Exceptions\InvalidAnneeScolaireException;
use App\Filament\Scolarite\Resources\AnneeScolaireResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class EditAnneeScolaire extends EditRecord
{
    protected static string $resource = AnneeScolaireResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(AnneeScolaireService::class)->modifier(auth()->user(), $record->uuid, new UpdateAnneeScolaireDto(
                libelle: $data['libelle'] ?? null,
                dateDebut: isset($data['date_debut']) ? (string) $data['date_debut'] : null,
                dateFin: isset($data['date_fin']) ? (string) $data['date_fin'] : null,
            ));
        } catch (InvalidAnneeScolaireException $e) {
            throw ValidationException::withMessages(['data.date_fin' => $e->getMessage()]);
        }
    }
}
