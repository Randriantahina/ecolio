<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Scolarite\Application\DTOs\CreateAnneeScolaireDto;
use Modules\Scolarite\Application\Services\AnneeScolaireService;
use Modules\Scolarite\Domain\Exceptions\InvalidAnneeScolaireException;
use Modules\Scolarite\Filament\Resources\AnneeScolaireResource;

final class CreateAnneeScolaire extends CreateRecord
{
    protected static string $resource = AnneeScolaireResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AnneeScolaireService::class)->creer(auth()->user(), new CreateAnneeScolaireDto(
                libelle: $data['libelle'],
                dateDebut: (string) $data['date_debut'],
                dateFin: (string) $data['date_fin'],
                estCourante: (bool) ($data['est_courante'] ?? false),
            ));
        } catch (InvalidAnneeScolaireException $e) {
            throw ValidationException::withMessages(['data.date_fin' => $e->getMessage()]);
        }
    }
}
