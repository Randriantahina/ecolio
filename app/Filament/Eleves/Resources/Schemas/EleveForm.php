<?php

declare(strict_types=1);

namespace App\Filament\Eleves\Resources\Schemas;

use App\Application\Scolarite\Services\StructureService;
use App\Domain\Eleves\Models\Eleve;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class EleveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('matricule')
                ->label('Matricule')
                ->helperText('Généré automatiquement. Seul le directeur peut le corriger.')
                ->maxLength(30)
                // RG-E1: read-only for everybody but the directeur; on creation it stays generated.
                ->disabled(fn (?Eleve $record): bool => $record === null || ! auth()->user()->can('corrigerMatricule', Eleve::class))
                ->dehydrated(fn (?Eleve $record): bool => $record !== null && auth()->user()->can('corrigerMatricule', Eleve::class)),
            TextInput::make('nom')->label('Nom')->required()->maxLength(120),
            TextInput::make('prenoms')->label('Prénoms')->maxLength(160),
            Select::make('sexe')->label('Sexe')->options(['F' => 'Féminin', 'M' => 'Masculin']),
            DatePicker::make('date_naissance')->label('Date de naissance')->maxDate(now()),
            TextInput::make('lieu_naissance')->label('Lieu de naissance')->maxLength(120),
            Select::make('classe_uuid')
                ->label('Classe')
                ->options(fn (): array => app(StructureService::class)->classes(auth()->user(), null)->pluck('nom', 'uuid')->all())
                ->visibleOn('create')
                ->helperText('Classes de l\'année scolaire courante.'),
            Toggle::make('est_redoublant')->label('Redoublant')->visibleOn('create'),
            Toggle::make('confirmer_doublon')
                ->label('Confirmer : il s\'agit bien d\'un autre élève')
                ->visibleOn('create')
                ->visible(fn ($livewire): bool => (bool) ($livewire->doublonDetecte ?? false)),
        ]);
    }
}
