<?php

declare(strict_types=1);

namespace App\Filament\Scolarite\Resources;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Filament\Scolarite\Resources\Pages\CreateAnneeScolaire;
use App\Filament\Scolarite\Resources\Pages\EditAnneeScolaire;
use App\Filament\Scolarite\Resources\Pages\ListAnneesScolaires;
use App\Filament\Scolarite\Resources\Schemas\AnneeScolaireForm;
use App\Filament\Scolarite\Resources\Tables\AnneesScolairesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class AnneeScolaireResource extends Resource
{
    protected static ?string $model = AnneeScolaire::class;

    protected static ?string $modelLabel = 'année scolaire';

    protected static ?string $pluralModelLabel = 'années scolaires';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return AnneeScolaireForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnneesScolairesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnneesScolaires::route('/'),
            'create' => CreateAnneeScolaire::route('/create'),
            'edit' => EditAnneeScolaire::route('/{record}/edit'),
        ];
    }
}
