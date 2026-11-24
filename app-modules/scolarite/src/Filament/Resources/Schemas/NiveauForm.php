<?php

declare(strict_types=1);

namespace Modules\Scolarite\Filament\Resources\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Modules\Scolarite\Domain\Enums\Cycle;

final class NiveauForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Niveau')->placeholder('6e')->required()->maxLength(60),
            Select::make('cycle')->label('Cycle')->required()->default(Cycle::Autre->value)->options(self::cycles()),
            TextInput::make('ordre')->label('Ordre')->numeric()->minValue(0)->default(0)
                ->helperText('Sert à trier les niveaux (et à proposer la classe suivante).'),
        ]);
    }

    /** @return array<string, string> */
    public static function cycles(): array
    {
        return [
            Cycle::Maternelle->value => 'Maternelle',
            Cycle::Primaire->value => 'Primaire',
            Cycle::College->value => 'Collège',
            Cycle::Lycee->value => 'Lycée',
            Cycle::Autre->value => 'Autre',
        ];
    }
}
