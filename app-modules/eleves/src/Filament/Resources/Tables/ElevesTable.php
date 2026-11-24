<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Eleves\Filament\Support\StatutEleveLabels;
use Modules\Scolarite\Application\Services\StructureService;

final class ElevesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricule')->label('Matricule')->searchable()->sortable()->fontFamily('mono'),
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('prenoms')->label('Prénoms')->searchable(),
                TextColumn::make('inscriptionActive.classe.nom')->label('Classe')->placeholder('Sans classe'),
                TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn (StatutEleve $state): string => StatutEleveLabels::label($state))
                    ->color(fn (StatutEleve $state): string => StatutEleveLabels::color($state)),
            ])
            ->defaultSort('matricule')
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutEleveLabels::options())->default(StatutEleve::Actif->value),
                SelectFilter::make('classe')->label('Classe')
                    ->options(fn (): array => app(StructureService::class)->classes(auth()->user(), null)->pluck('nom', 'uuid')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $uuid) => $q->whereHas('inscriptionActive.classe', fn (Builder $c) => $c->where('uuid', $uuid)),
                    )),
            ])
            ->recordActions([EditAction::make()->label('Ouvrir')])
            ->emptyStateHeading('Aucun élève')
            ->emptyStateDescription('Ajoutez le premier élève de l\'école.');
    }
}
