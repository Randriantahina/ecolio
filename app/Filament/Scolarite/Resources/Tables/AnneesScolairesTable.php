<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources\Tables;

use App\Application\Scolarite\Services\AnneeScolaireService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AnneesScolairesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('libelle')->label('Année')->sortable()->weight('bold'),
                TextColumn::make('date_debut')->label('Début')->date('d/m/Y'),
                TextColumn::make('date_fin')->label('Fin')->date('d/m/Y'),
                IconColumn::make('est_courante')->label('Courante')->boolean(),
            ])
            ->defaultSort('date_debut', 'desc')
            ->recordActions([
                Action::make('definirCourante')
                    ->label('Définir comme courante')
                    ->visible(fn (AnneeScolaire $record): bool => ! $record->est_courante && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->action(function (AnneeScolaire $record): void {
                        app(AnneeScolaireService::class)->definirCourante(auth()->user(), $record->uuid);
                        Notification::make()->success()->title("{$record->libelle} est l'année courante")->send();
                    }),
                EditAction::make()->label('Modifier'),
            ])
            ->emptyStateHeading('Aucune année scolaire')
            ->emptyStateDescription('Créez l\'année en cours pour pouvoir ouvrir des classes.');
    }
}
