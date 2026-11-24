<?php

declare(strict_types=1);

namespace Modules\Eleves\Filament\Support;

use Modules\Eleves\Domain\Enums\StatutEleve;

/** French wording and badge colours live in the UI layer, not in the domain enum. */
final class StatutEleveLabels
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(StatutEleve::cases())->mapWithKeys(fn (StatutEleve $s) => [$s->value => self::label($s)])->all();
    }

    public static function label(StatutEleve $statut): string
    {
        return $statut->libelle();
    }

    public static function color(StatutEleve $statut): string
    {
        return $statut === StatutEleve::Actif ? 'success' : 'gray';
    }
}
