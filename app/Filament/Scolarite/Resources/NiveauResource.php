<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources;

use App\Domain\Scolarite\Enums\Cycle;
use App\Domain\Scolarite\Models\Niveau;
use App\Filament\Scolarite\Resources\Pages\CreateNiveau;
use App\Filament\Scolarite\Resources\Pages\EditNiveau;
use App\Filament\Scolarite\Resources\Pages\ListNiveaux;
use App\Filament\Scolarite\Resources\Schemas\NiveauForm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class NiveauResource extends Resource
{
    protected static ?string $model = Niveau::class;

    protected static ?string $modelLabel = 'niveau';

    protected static ?string $pluralModelLabel = 'niveaux';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return NiveauForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Niveau')->weight('bold')->searchable(),
                TextColumn::make('cycle')->label('Cycle')->badge()
                    ->formatStateUsing(fn (Cycle $state): string => NiveauForm::cycles()[$state->value]),
                TextColumn::make('ordre')->label('Ordre')->sortable(),
            ])
            ->defaultSort('ordre')
            ->recordActions([EditAction::make()->label('Modifier')])
            ->emptyStateHeading('Aucun niveau')
            ->emptyStateDescription('Ajoutez les niveaux de l\'école (6e, 5e, CM2…).');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNiveaux::route('/'),
            'create' => CreateNiveau::route('/create'),
            'edit' => EditNiveau::route('/{record}/edit'),
        ];
    }
}
