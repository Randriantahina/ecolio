<?php

declare(strict_types=1);

namespace Modules\Absences\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Absences\Application\Services\AbsenceService;
use Modules\Absences\Domain\Models\Absence;
use Modules\Absences\Filament\Resources\Pages\ListAbsences;
use Modules\Scolarite\Application\Services\StructureService;

/** The absences register: the list the roll-call page feeds, to review, justify or correct. */
final class AbsenceResource extends Resource
{
    protected static ?string $model = Absence::class;

    protected static ?string $modelLabel = 'absence';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static string|\UnitEnum|null $navigationGroup = 'Vie scolaire';

    protected static ?string $navigationLabel = 'Registre des absences';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['eleve', 'classe']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('eleve.nom')->label('Élève')->searchable(['nom', 'prenoms', 'matricule'])
                    ->description(fn (Absence $a): string => (string) $a->eleve?->matricule),
                TextColumn::make('classe.nom')->label('Classe'),
                TextColumn::make('periode')->label('Période')->badge()
                    ->formatStateUsing(fn (string $state): string => self::periodes()[$state] ?? $state),
                IconColumn::make('justifiee')->label('Justifiée')->boolean(),
                TextColumn::make('motif')->label('Motif')->placeholder('—')->limit(40),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('classe')->label('Classe')->attribute('classe_id')
                    ->options(fn (): array => app(StructureService::class)->classes(auth()->user(), null)->pluck('nom', 'id')->all()),
                SelectFilter::make('periode')->label('Période')->options(self::periodes()),
                TernaryFilter::make('justifiee')->label('Justifiée'),
                Filter::make('periode_dates')->label('Dates')
                    ->schema([DatePicker::make('du')->label('Du'), DatePicker::make('au')->label('Au')])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['du'] ?? null, fn (Builder $q, string $du) => $q->whereDate('date', '>=', $du))
                        ->when($data['au'] ?? null, fn (Builder $q, string $au) => $q->whereDate('date', '<=', $au))),
            ])
            ->recordActions([
                Action::make('justifier')->label('Justifier')->icon(Heroicon::OutlinedCheckBadge)
                    ->visible(fn (Absence $a): bool => ! $a->justifiee)
                    ->schema([Textarea::make('motif')->label('Motif')->maxLength(255)])
                    ->action(function (Absence $a, array $data): void {
                        app(AbsenceService::class)->justifier(auth()->user(), $a->id, $data['motif'] ?? null);
                        Notification::make()->success()->title('Absence justifiée')->send();
                    }),
                Action::make('retirer')->label('Retirer')->color('danger')->icon(Heroicon::OutlinedTrash)
                    ->requiresConfirmation()->modalDescription('L\'élève sera de nouveau considéré présent ce jour-là.')
                    ->action(function (Absence $a): void {
                        app(AbsenceService::class)->retirer(auth()->user(), $a->id);
                        Notification::make()->success()->title('Absence retirée')->send();
                    }),
            ])
            ->emptyStateHeading('Aucune absence')
            ->emptyStateDescription('Faites l\'appel du jour pour enregistrer les absences.');
    }

    /** @return array<string, string> */
    public static function periodes(): array
    {
        return ['journee' => 'Journée', 'matin' => 'Matin', 'apres_midi' => 'Après-midi'];
    }

    public static function getPages(): array
    {
        return ['index' => ListAbsences::route('/')];
    }
}
