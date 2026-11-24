<?php

declare(strict_types=1);

namespace Modules\Dynamique\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Enums\EntiteChamp;
use Modules\Dynamique\Domain\Enums\TypeChamp;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;
use Modules\Dynamique\Filament\Resources\Pages\CreateChampPersonnalise;
use Modules\Dynamique\Filament\Resources\Pages\EditChampPersonnalise;
use Modules\Dynamique\Filament\Resources\Pages\ListChampsPersonnalises;

final class ChampPersonnaliseResource extends Resource
{
    protected static ?string $model = ChampPersonnalise::class;

    protected static ?string $modelLabel = 'champ personnalisé';

    protected static ?string $pluralModelLabel = 'champs personnalisés';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Le champ')
                ->description('Une colonne propre à votre école, ajoutée à la fiche et au registre.')
                ->columns(2)
                ->schema([
                    Select::make('entite')->label('S\'applique à')->required()->visibleOn('create')->default(EntiteChamp::Eleve->value)
                        ->options(['eleve' => 'Élèves', 'tuteur' => 'Tuteurs']),
                    TextInput::make('libelle')->label('Libellé')->required()->maxLength(120)->placeholder('Quartier')
                        ->helperText('Vous pouvez le renommer plus tard : la clé technique, elle, ne change jamais.'),
                    Toggle::make('obligatoire')->label('Obligatoire')->helperText('La fiche ne s\'enregistre pas sans cette valeur.'),
                ]),
            Section::make('Type de valeur')
                ->description('Changer le type n\'est possible que si les valeurs déjà saisies sont convertibles.')
                ->schema([
                    Select::make('type')->label('Type')->required()->live()->default(TypeChamp::Texte->value)->options(self::types()),
                    TagsInput::make('options')->label('Choix possibles')->visible(fn (Get $get): bool => $get('type') === TypeChamp::Liste->value)
                        ->helperText('Tapez un choix puis Entrée.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('libelle')->label('Libellé')->weight('bold')->searchable(),
                TextColumn::make('cle')->label('Clé technique')->fontFamily('mono')->color('gray'),
                TextColumn::make('entite')->label('Pour')->badge()->formatStateUsing(fn (EntiteChamp $state): string => $state === EntiteChamp::Eleve ? 'Élèves' : 'Tuteurs'),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (TypeChamp $state): string => self::types()[$state->value]),
                IconColumn::make('obligatoire')->label('Obligatoire')->boolean(),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->filters([
                SelectFilter::make('entite')->label('Pour')->options(['eleve' => 'Élèves', 'tuteur' => 'Tuteurs']),
                TernaryFilter::make('actif')->label('Actif')->default(true)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('actif', true),
                        false: fn (Builder $query): Builder => $query->where('actif', false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
                Action::make('desactiver')->label('Désactiver')->color('danger')->icon(Heroicon::OutlinedEyeSlash)
                    ->visible(fn (ChampPersonnalise $c): bool => $c->actif && auth()->user()->can('manage', ChampPersonnalise::class))
                    ->requiresConfirmation()->modalDescription('Le champ disparaît des formulaires et des colonnes ; les valeurs déjà saisies sont conservées.')
                    ->action(function (ChampPersonnalise $c): void {
                        app(ChampService::class)->desactiver(auth()->user(), $c->uuid);
                        Notification::make()->success()->title('Champ désactivé')->send();
                    }),
                Action::make('reactiver')->label('Réactiver')->icon(Heroicon::OutlinedEye)
                    ->visible(fn (ChampPersonnalise $c): bool => ! $c->actif && auth()->user()->can('manage', ChampPersonnalise::class))
                    ->action(function (ChampPersonnalise $c): void {
                        app(ChampService::class)->reactiver(auth()->user(), $c->uuid);
                        Notification::make()->success()->title('Champ réactivé')->send();
                    }),
            ])
            ->emptyStateHeading('Aucun champ personnalisé')
            ->emptyStateDescription('Ajoutez par exemple « Quartier » ou « Boursier » : ils apparaîtront dans la fiche élève et le registre.');
    }

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            TypeChamp::Texte->value => 'Texte', TypeChamp::Nombre->value => 'Nombre', TypeChamp::Date->value => 'Date',
            TypeChamp::Booleen->value => 'Oui / non', TypeChamp::Liste->value => 'Liste de choix', TypeChamp::Telephone->value => 'Téléphone',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChampsPersonnalises::route('/'),
            'create' => CreateChampPersonnalise::route('/create'),
            'edit' => EditChampPersonnalise::route('/{record}/edit'),
        ];
    }
}
