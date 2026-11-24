<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Contracts;

/** Port for tabular exports (US3.5, M10): any module hands over headings and rows, the adapter writes the file. */
interface ExporteurTableau
{
    public const FORMATS = ['xlsx', 'csv'];

    /**
     * @param  list<string>  $entetes
     * @param  list<list<scalar|null>>  $lignes
     * @param  'xlsx'|'csv'  $format
     */
    public function contenu(array $entetes, array $lignes, string $format): string;

    public function nomDeFichier(string $base, string $format): string;
}
