<?php

declare(strict_types=1);

namespace App\Application\Eleves\Services;

use App\Domain\Eleves\Contracts\EleveRepository;
use App\Domain\Finance\Contracts\CompteurRepository;
use App\Domain\Platform\Contracts\EcoleRepository;

/**
 * RG-M1 / US2.4: the format belongs to the school (`ecoles.format_matricule`:
 * prefixe, avec_annee, longueur). Defaults give `2026-0001`.
 */
final class MatriculeGenerator
{
    public function __construct(
        private readonly EcoleRepository $ecoles,
        private readonly CompteurRepository $compteurs,
        private readonly EleveRepository $eleves,
    ) {}

    public function next(int $ecoleId): string
    {
        $format = $this->ecoles->findOrFail($ecoleId)->format_matricule ?? [];
        $prefixe = (string) ($format['prefixe'] ?? '');
        $longueur = max(1, (int) ($format['longueur'] ?? 4));
        $annee = ($format['avec_annee'] ?? true) ? now()->year.'-' : '';

        // A matricule typed by hand or imported may already hold the next number: skip over it
        // rather than fail, since RG-E1 makes the matricule unique and never reused.
        do {
            $numero = $this->compteurs->nextValue($ecoleId, 'matricule');
            $matricule = $prefixe.$annee.str_pad((string) $numero, $longueur, '0', STR_PAD_LEFT);
        } while ($this->eleves->matriculeExists($ecoleId, $matricule));

        return $matricule;
    }
}
