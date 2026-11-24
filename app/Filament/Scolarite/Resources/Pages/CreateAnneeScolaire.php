<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Pages;

use App\Application\Scolarite\DTOs\CreateAnneeScolaireDto;
use App\Application\Scolarite\Services\AnneeScolaireService;
use App\Domain\Scolarite\Exceptions\InvalidAnneeScolaireException;
use App\Filament\Scolarite\Resources\AnneeScolaireResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
