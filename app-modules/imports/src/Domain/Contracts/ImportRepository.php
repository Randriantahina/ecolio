<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Domain\Models\ImportLigne;

interface ImportRepository
{
    public function findOrFail(string $uuid, int $ecoleId): Import;

    public function save(Import $import): void;

    /** @param list<array<string, mixed>> $lignes rows already shaped for `import_lignes` (without import_id) */
    public function remplacerLignes(Import $import, array $lignes): void;

    /** @return Collection<int, ImportLigne> ordered by file row number */
    public function lignes(Import $import, ?string $statut = null): Collection;

    public function ligneOrFail(Import $import, int $numero): ImportLigne;

    public function saveLigne(ImportLigne $ligne): void;

    /** @return array<string, string> normalised header => target */
    public function correspondances(int $ecoleId, EntiteImport $entite): array;

    public function memoriserCorrespondance(int $ecoleId, EntiteImport $entite, string $entete, string $cible): void;
}
