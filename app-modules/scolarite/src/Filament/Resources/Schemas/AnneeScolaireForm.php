<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AnneeScolaireForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Année scolaire')
                ->description('Période et statut de l\'année.')
                ->columns(2)
                ->schema([
                    TextInput::make('libelle')->label('Libellé')->placeholder('2026-2027')->required()->maxLength(20),
                    DatePicker::make('date_debut')->label('Début')->required(),
                    DatePicker::make('date_fin')->label('Fin')->required()->after('date_debut'),
                    Toggle::make('est_courante')->label('Année courante')
                        ->helperText('Une seule année est courante : la précédente cesse de l\'être.')
                        ->visibleOn('create'),
                ]),
        ]);
    }
}
