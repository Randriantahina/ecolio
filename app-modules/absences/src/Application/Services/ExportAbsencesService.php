<?php

declare(strict_types=1);

namespace Modules\Absences\Application\Services;

use Illuminate\Support\Collection;
use Modules\Absences\Domain\Models\Absence;

/** "Absences par période" export (M10): the caller passes the rows of the current filtered view. */
final class ExportAbsencesService
{
    private const PERIODES = ['journee' => 'Journée', 'matin' => 'Matin', 'apres_midi' => 'Après-midi'];

    /**
     * @param  Collection<int, Absence>  $absences
     * @return list<list<scalar|null>> headings first, then one row per absence
     */
    public function tableau(Collection $absences): array
    {
        $absences->loadMissing(['eleve', 'classe']);

        return [
            ['Date', 'Matricule', 'Élève', 'Classe', 'Période', 'Justifiée', 'Motif'],
            ...$absences->map(fn (Absence $a): array => [
                $a->date->format('d/m/Y'), $a->eleve->matricule, trim($a->eleve->nom.' '.($a->eleve->prenoms ?? '')),
                $a->classe?->nom, self::PERIODES[$a->periode] ?? $a->periode, $a->justifiee ? 'Oui' : 'Non', $a->motif,
            ])->values()->all(),
        ];
    }
}
