<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Finance\Domain\Enums\Periodicite;
use Modules\Finance\Domain\Models\TypeFrais;
use Modules\Finance\Filament\Resources\Pages\CreateTypeFrais;
use Modules\Finance\Filament\Resources\Pages\EditTypeFrais;
use Modules\Finance\Filament\Resources\Pages\ListTypesFrais;

final class TypeFraisResource extends Resource
{
    protected static ?string $model = TypeFrais::class;

    protected static ?string $modelLabel = 'type de frais';

    protected static ?string $pluralModelLabel = 'types de frais';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordRouteKeyName = 'uuid';

    /** @return array<string, string> */
    public static function periodicites(): array
    {
        return [
            Periodicite::Unique->value => 'Unique',
            Periodicite::Mensuel->value => 'Mensuel',
            Periodicite::Trimestriel->value => 'Trimestriel',
            Periodicite::Annuel->value => 'Annuel',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Nom')->placeholder('Écolage')->required()->maxLength(80),
            Select::make('periodicite')->label('Périodicité')->required()->options(self::periodicites())
                ->default(Periodicite::Mensuel->value)
                ->helperText('Mensuel : une échéance par mois de l\'année scolaire.'),
            Toggle::make('est_obligatoire')->label('Obligatoire')->default(true),
            Toggle::make('actif')->label('Actif')->default(true)
                ->helperText('Un type inactif ne génère plus d\'échéances.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Frais')->weight('bold')->searchable(),
                TextColumn::make('periodicite')->label('Périodicité')->badge()
                    ->formatStateUsing(fn (Periodicite $state): string => self::periodicites()[$state->value]),
                IconColumn::make('est_obligatoire')->label('Obligatoire')->boolean(),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Modifier')])
            ->emptyStateHeading('Aucun type de frais')
            ->emptyStateDescription('Commencez par l\'écolage, puis ajoutez inscription, examens…');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTypesFrais::route('/'),
            'create' => CreateTypeFrais::route('/create'),
            'edit' => EditTypeFrais::route('/{record}/edit'),
        ];
    }
}
