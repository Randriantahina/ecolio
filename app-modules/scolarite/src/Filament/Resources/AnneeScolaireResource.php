<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Scolarite\Domain\Models\AnneeScolaire;
use Modules\Scolarite\Filament\Resources\Pages\CreateAnneeScolaire;
use Modules\Scolarite\Filament\Resources\Pages\EditAnneeScolaire;
use Modules\Scolarite\Filament\Resources\Pages\ListAnneesScolaires;
use Modules\Scolarite\Filament\Resources\Schemas\AnneeScolaireForm;
use Modules\Scolarite\Filament\Resources\Tables\AnneesScolairesTable;

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
