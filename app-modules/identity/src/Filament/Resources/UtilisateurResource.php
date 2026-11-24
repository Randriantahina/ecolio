<?php

declare(strict_types=1);

namespace Modules\Identity\Filament\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Modules\Identity\Domain\Enums\CanalInvitation;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Filament\Resources\Pages\CreateUtilisateur;
use Modules\Identity\Filament\Resources\Pages\EditUtilisateur;
use Modules\Identity\Filament\Resources\Pages\ListUtilisateurs;
use Modules\Platform\Filament\Clusters\ParametresCluster;

final class UtilisateurResource extends Resource
{
    protected static ?string $model = Utilisateur::class;

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = ParametresCluster::class;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordRouteKeyName = 'uuid';

    protected static ?string $recordTitleAttribute = 'nom';

    protected static bool $isScopedToTenant = false;

    /** @return array<string, string> */
    public static function rolesAssignables(): array
    {
        return [
            Role::Administration->value => 'Administration (direction de l\'école)',
            Role::Enseignant->value => 'Enseignant (absences uniquement)',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')->columns(2)->schema([
                TextInput::make('nom')->label('Nom complet')->required()->maxLength(120),
                Select::make('role')->label('Rôle')->required()->options(self::rolesAssignables())
                    ->helperText('Les droits précis de chaque rôle se règlent dans « Rôles ».'),
                TextInput::make('email')->label('Email')->email()->maxLength(190)
                    ->required(fn (Get $get): bool => $get('canal') === CanalInvitation::Email->value),
                TextInput::make('telephone')->label('Téléphone')->tel()->maxLength(30)
                    ->required(fn (Get $get): bool => $get('canal') === CanalInvitation::Sms->value),
            ]),
            Section::make('Invitation')
                ->description('La personne reçoit son identifiant et un mot de passe provisoire par le canal choisi.')
                ->visibleOn('create')
                ->schema([
                    Radio::make('canal')->label('Envoyer l\'invitation par')->required()->live()->inline()
                        ->default(CanalInvitation::Email->value)
                        ->options(collect(CanalInvitation::cases())->mapWithKeys(fn (CanalInvitation $c): array => [$c->value => $c->libelle()])->all()),
                ]),
            Section::make('Accès')->visibleOn('edit')->schema([
                Toggle::make('actif')->label('Compte actif')->helperText('Désactiver coupe l\'accès tout de suite, sans supprimer l\'historique.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->weight('bold')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->placeholder('—'),
                TextColumn::make('telephone')->label('Téléphone')->searchable()->placeholder('—'),
                TextColumn::make('roles.name')->label('Rôle')->badge()
                    ->formatStateUsing(fn (string $state): string => self::rolesAssignables()[$state] ?? $state),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options(self::rolesAssignables())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null) ? $query->role($data['value']) : $query),
                TernaryFilter::make('actif')->label('Compte actif'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUtilisateurs::route('/'),
            'create' => CreateUtilisateur::route('/create'),
            'edit' => EditUtilisateur::route('/{record}/edit'),
        ];
    }

    /** Utilisateur carries no global tenant scope (login and super admins read across schools), so the page scopes itself. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('ecole_id', Filament::getTenant()?->getKey());
    }
}
