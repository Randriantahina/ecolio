<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Contracts;

use Modules\Dynamique\Domain\Enums\EntiteChamp;

/**
 * Port implemented by the module that owns the entity (Eleves, Tuteurs): lets this module check a type
 * change (RG-D3) without reading another module's tables.
 */
interface LecteurValeursChamps
{
    public function supporte(EntiteChamp $entite): bool;

    /** @return array<string, mixed> row label ("MAT-001 Rakoto") => stored value, rows without a value omitted. */
    public function valeurs(int $ecoleId, EntiteChamp $entite, string $cle): array;
}
