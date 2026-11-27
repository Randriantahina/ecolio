<?php

declare(strict_types=1);

namespace Modules\Imports\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Imports\Domain\Enums\ModeImport;
use Modules\Imports\Domain\Enums\StatutImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Filament\Pages\ImporterEleves;
use Modules\Imports\Filament\Resources\Pages\ControlerImport;
use Modules\Imports\Filament\Resources\Pages\ListImports;

/** History of the school's imports; each row opens the control screen (steps 3 to 5). */
final class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $modelLabel = 'import';

    protected static ?string $pluralModelLabel = 'imports Excel';

    protected static ?string $navigationLabel = 'Imports Excel';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Données';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('nom_original')->label('Fichier')->weight('bold')->searchable(),
                TextColumn::make('mode')->label('Mode')->badge()
                    ->formatStateUsing(fn (ModeImport $state): string => $state === ModeImport::Creation ? 'Création' : 'Mise à jour'),
                TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn (StatutImport $state): string => self::statuts()[$state->value])
                    ->color(fn (StatutImport $state): string => match ($state) {
                        StatutImport::Termine => 'success', StatutImport::Echec => 'danger', StatutImport::Annule => 'gray', default => 'warning',
                    }),
                TextColumn::make('total')->label('Lignes'),
                TextColumn::make('crees')->label('Créés'),
                TextColumn::make('mis_a_jour')->label('Mis à jour'),
                TextColumn::make('ignores')->label('Ignorés'),
                TextColumn::make('en_erreur')->label('En erreur')->color('danger'),
            ])
            ->recordUrl(fn (Import $record): string => self::getUrl('controle', ['record' => $record]))
            ->recordActions([Action::make('ouvrir')->label('Ouvrir')->icon(Heroicon::OutlinedEye)
                ->url(fn (Import $record): string => self::getUrl('controle', ['record' => $record]))]);
    }

    /** @return array<string, string> */
    public static function statuts(): array
    {
        return [
            'envoye' => 'Fichier envoyé', 'mappe' => 'Colonnes associées', 'valide' => 'À contrôler', 'en_cours' => 'Import en cours',
            'termine' => 'Terminé', 'echec' => 'Échec', 'annule' => 'Annulé',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImports::route('/'),
            'controle' => ControlerImport::route('/{record}/controle'),
        ];
    }

    public static function urlNouvelImport(): string
    {
        return ImporterEleves::getUrl();
    }
}
