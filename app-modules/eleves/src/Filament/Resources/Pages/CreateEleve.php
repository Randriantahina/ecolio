<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Eleves\Application\DTOs\CreateEleveDto;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Domain\Exceptions\DuplicateEleveException;
use Modules\Eleves\Filament\Resources\EleveResource;

final class CreateEleve extends CreateRecord
{
    protected static string $resource = EleveResource::class;

    /** Flips on after a likely duplicate was found, which reveals the confirmation switch. */
    public bool $doublonDetecte = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $result = app(EleveService::class)->creer(auth()->user(), new CreateEleveDto(
                nom: $data['nom'],
                prenoms: $data['prenoms'] ?? null,
                sexe: $data['sexe'] ?? null,
                dateNaissance: $data['date_naissance'] ?? null,
                lieuNaissance: $data['lieu_naissance'] ?? null,
                matricule: null,
                classeUuid: $data['classe_uuid'] ?? null,
                typeInscription: 'nouvelle',
                estRedoublant: (bool) ($data['est_redoublant'] ?? false),
                confirmerDoublon: (bool) ($data['confirmer_doublon'] ?? false),
                champsPerso: $data['champs_perso'] ?? [],
            ));
        } catch (DuplicateEleveException $e) {
            // RG-E4: a question, not an error. The user confirms with the switch and saves again.
            $this->doublonDetecte = true;
            Notification::make()->warning()->persistent()
                ->title('Doublon probable')
                ->body("{$e->existant->nom} ({$e->existant->matricule}) existe déjà avec la même date de naissance. Activez la confirmation puis enregistrez de nouveau.")
                ->send();

            $this->halt();
        }

        if ($result['effectif_depasse']) {
            Notification::make()->warning()->title('Effectif maximal dépassé')
                ->body('L\'inscription est enregistrée, mais la classe dépasse son effectif maximal.')->send();
        }

        return $result['eleve'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
