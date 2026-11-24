<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources;

use App\Application\Finance\Services\FraisService;
use App\Application\Scolarite\Services\StructureService;
use App\Domain\Finance\Models\Tarif;
use App\Filament\Finance\Resources\Pages\CreateTarif;
use App\Filament\Finance\Resources\Pages\EditTarif;
use App\Filament\Finance\Resources\Pages\ListTarifs;
use App\Filament\Finance\Support\Ariary;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TarifResource extends Resource
{
    protected static ?string $model = Tarif::class;

    protected static ?string $modelLabel = 'tarif';

    protected static ?string $pluralModelLabel = 'tarifs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type_frais_uuid')->label('Type de frais')->required()->visibleOn('create')
                ->options(fn (): array => app(FraisService::class)->types(auth()->user())->where('actif', true)->pluck('nom', 'uuid')->all()),
            TextInput::make('montant_ar')->label('Montant')->suffix('Ar')->numeric()->integer()->minValue(0)->required()
                ->helperText('Modifier un montant ne change pas les échéances déjà émises.'),
            // The portée is derived from the filled field: the service refuses both at once.
            Select::make('niveau_uuid')->label('Niveau (facultatif)')->visibleOn('create')
                ->options(fn (): array => app(StructureService::class)->niveaux(auth()->user())->pluck('nom', 'uuid')->all())
                ->disabled(fn (Get $get): bool => filled($get('classe_uuid')))->live(),
            Select::make('classe_uuid')->label('Classe (facultatif)')->visibleOn('create')
                ->options(fn (): array => app(StructureService::class)->classes(auth()->user(), null)->pluck('nom', 'uuid')->all())
                ->disabled(fn (Get $get): bool => filled($get('niveau_uuid')))->live()
                ->helperText('Sans niveau ni classe, le tarif vaut pour toute l\'école. Le plus précis l\'emporte.'),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['typeFrais', 'niveau', 'classe'])
            ->whereHas('anneeScolaire', fn (Builder $annee) => $annee->where('est_courante', true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('typeFrais.nom')->label('Frais')->weight('bold'),
                TextColumn::make('portee')->label('Portée')
                    ->state(fn (Tarif $record): string => $record->classe?->nom ?? $record->niveau?->nom ?? 'Toute l\'école'),
                TextColumn::make('montant_ar')->label('Montant')->formatStateUsing(fn (int $state): string => Ariary::format($state))->alignEnd(),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
                DeleteAction::make()->label('Supprimer')
                    ->using(fn (Tarif $record) => app(FraisService::class)->supprimerTarif(auth()->user(), $record->uuid)),
            ])
            ->emptyStateHeading('Aucun tarif pour l\'année courante')
            ->emptyStateDescription('Un tarif génère les échéances de chaque élève inscrit.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTarifs::route('/'),
            'create' => CreateTarif::route('/create'),
            'edit' => EditTarif::route('/{record}/edit'),
        ];
    }
}
