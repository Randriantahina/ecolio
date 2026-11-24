<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Scolarite\Application\Services\StructureService;

final class ClasseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Classe')
                ->description('Nom, niveau et capacité.')
                ->columns(2)
                ->schema([
                    TextInput::make('nom')->label('Classe')->placeholder('6e A')->required()->maxLength(60),
                    // The level of an existing class is not editable: its pupils and fees follow the level.
                    Select::make('niveau_uuid')->label('Niveau')->required()->visibleOn('create')
                        ->options(fn (): array => app(StructureService::class)->niveaux(auth()->user())->pluck('nom', 'uuid')->all()),
                    TextInput::make('effectif_max')->label('Effectif maximal')->numeric()->minValue(1)
                        ->helperText('Un dépassement avertit sans bloquer l\'inscription (RG-I2).'),
                ]),
        ]);
    }
}
