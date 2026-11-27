<?php

declare(strict_types=1);

namespace Modules\Imports\Application\Services;

use Modules\Dynamique\Domain\Enums\TypeChamp;

/** "New field to create (guessed type)": looks at a column's sample values, defaults to free text. */
final class DevineType
{
    /** @param list<scalar|null> $echantillon */
    public function pour(array $echantillon): TypeChamp
    {
        $valeurs = array_values(array_filter(array_map(fn ($v) => $v === null ? null : trim((string) $v), $echantillon), fn ($v) => $v !== null && $v !== ''));
        if ($valeurs === []) {
            return TypeChamp::Texte;
        }

        $tous = fn (callable $test): bool => count(array_filter($valeurs, $test)) === count($valeurs);

        return match (true) {
            $tous(fn ($v) => in_array(ValidationLigneEleve::cle($v), ['oui', 'non', 'true', 'false', 'vrai', 'faux'], true)) => TypeChamp::Booleen,
            $tous(fn ($v) => is_numeric($v)) => TypeChamp::Nombre,
            $tous(fn ($v) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$|^\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}$/', $v)) => TypeChamp::Date,
            default => TypeChamp::Texte,
        };
    }
}
