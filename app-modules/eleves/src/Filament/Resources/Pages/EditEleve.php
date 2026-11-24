<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Eleves\Application\DTOs\UpdateEleveDto;
use Modules\Eleves\Application\Services\DepartService;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Application\Services\InscriptionService;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\EleveResource;
use Modules\Scolarite\Application\Services\StructureService;

final class EditEleve extends EditRecord
{
    protected static string $resource = EleveResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->changerClasseAction(), $this->departAction()];
    }

    private function changerClasseAction(): Action
    {
        return Action::make('changerClasse')
            ->label('Changer de classe')
            ->visible(fn (): bool => $this->estActif() && auth()->user()->can('manage', Eleve::class))
            ->schema([
                Select::make('classe_uuid')->label('Nouvelle classe')->required()
                    ->options(fn (): array => app(StructureService::class)->classes(auth()->user(), null)->pluck('nom', 'uuid')->all()),
            ])
            ->action(function (array $data): void {
                $result = app(InscriptionService::class)->changerClasse(auth()->user(), $this->getRecord(), $data['classe_uuid']);
                Notification::make()->success()->title('Classe modifiée')->send();
                if ($result['effectif_depasse']) {
                    Notification::make()->warning()->title('Effectif maximal dépassé')->send();
                }
                $this->getRecord()->unsetRelation('inscriptionActive');
            });
    }

    private function departAction(): Action
    {
        return Action::make('depart')
            ->label('Enregistrer un départ')
            ->color('danger')
            ->visible(fn (): bool => $this->estActif() && auth()->user()->can('manage', Eleve::class))
            ->modalDescription('Les échéances futures non payées seront annulées. Les paiements reçus sont conservés.')
            ->schema([
                Select::make('statut')->label('Motif du départ')->required()->default(StatutEleve::Sorti->value)->options([
                    StatutEleve::Sorti->value => 'Sorti',
                    StatutEleve::Transfere->value => 'Transféré',
                    StatutEleve::Abandon->value => 'Abandon',
                    StatutEleve::Diplome->value => 'Diplômé',
                ]),
                DatePicker::make('date_sortie')->label('Date de sortie')->required()->default(now()),
                TextInput::make('motif_sortie')->label('Précision'),
                TextInput::make('commentaire_sortie')->label('Commentaire'),
            ])
            ->action(function (array $data): void {
                app(DepartService::class)->enregistrer(
                    auth()->user(), $this->getRecord()->uuid, StatutEleve::from($data['statut']),
                    $data['date_sortie'], $data['motif_sortie'] ?? null, $data['commentaire_sortie'] ?? null,
                );
                Notification::make()->success()->title('Départ enregistré')->send();
                $this->refreshFormData(['statut', 'date_sortie']);
            });
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(EleveService::class)->modifier(auth()->user(), $record->uuid, new UpdateEleveDto(
            nom: $data['nom'] ?? null,
            prenoms: $data['prenoms'] ?? null,
            sexe: $data['sexe'] ?? null,
            dateNaissance: isset($data['date_naissance']) ? (string) $data['date_naissance'] : null,
            lieuNaissance: $data['lieu_naissance'] ?? null,
            matricule: $data['matricule'] ?? null,
            champsPerso: $data['champs_perso'] ?? [],
        ));
    }

    private function estActif(): bool
    {
        return $this->getRecord()->statut === StatutEleve::Actif;
    }
}
