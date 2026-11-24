<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Filament\Resources\Pages\CreateEleve;
use Modules\Eleves\Filament\Resources\Pages\EditEleve;
use Modules\Eleves\Filament\Resources\Pages\ListEleves;
use Modules\Eleves\Filament\Resources\Schemas\EleveForm;
use Modules\Eleves\Filament\Resources\Tables\ElevesTable;

final class EleveResource extends Resource
{
    protected static ?string $model = Eleve::class;

    protected static ?string $modelLabel = 'élève';

    protected static ?string $pluralModelLabel = 'élèves';

    protected static ?string $navigationLabel = 'Élèves';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    // The public identifier is the uuid; the numeric id never reaches a URL.
    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return EleveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ElevesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('inscriptionActive.classe');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEleves::route('/'),
            'create' => CreateEleve::route('/create'),
            'edit' => EditEleve::route('/{record}/edit'),
        ];
    }
}
