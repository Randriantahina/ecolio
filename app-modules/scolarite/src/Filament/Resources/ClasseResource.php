<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Platform\Filament\Clusters\ParametresCluster;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Filament\Resources\Pages\CreateClasse;
use Modules\Scolarite\Filament\Resources\Pages\EditClasse;
use Modules\Scolarite\Filament\Resources\Pages\ListClasses;
use Modules\Scolarite\Filament\Resources\Schemas\ClasseForm;

final class ClasseResource extends Resource
{
    protected static ?string $model = Classe::class;

    protected static ?string $modelLabel = 'classe';

    protected static ?string $pluralModelLabel = 'classes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $cluster = ParametresCluster::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return ClasseForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['niveau', 'anneeScolaire']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Classe')->weight('bold')->searchable()->sortable(),
                TextColumn::make('niveau.nom')->label('Niveau'),
                TextColumn::make('anneeScolaire.libelle')->label('Année'),
                TextColumn::make('effectif_max')->label('Effectif max')->placeholder('Illimité'),
            ])
            ->defaultSort('nom')
            ->recordActions([
                EditAction::make()->label('Modifier'),
                DeleteAction::make()->label('Supprimer')
                    ->using(fn (Classe $record) => app(StructureService::class)->supprimerClasse(auth()->user(), $record->uuid)),
            ])
            ->emptyStateHeading('Aucune classe')
            ->emptyStateDescription('Créez les classes de l\'année courante.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClasses::route('/'),
            'create' => CreateClasse::route('/create'),
            'edit' => EditClasse::route('/{record}/edit'),
        ];
    }
}
