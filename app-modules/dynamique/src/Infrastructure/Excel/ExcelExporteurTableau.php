<?php

declare(strict_types=1);

namespace Modules\Dynamique\Infrastructure\Excel;

use InvalidArgumentException;
use Maatwebsite\Excel\Excel;
use Modules\Dynamique\Domain\Contracts\ExporteurTableau;

final class ExcelExporteurTableau implements ExporteurTableau
{
    public function __construct(private readonly Excel $excel) {}

    public function contenu(array $entetes, array $lignes, string $format): string
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException("Format d'export inconnu : $format");
        }

        $lignes = array_map(fn (array $ligne): array => array_map($this->neutraliser(...), $ligne), $lignes);

        return $format === 'csv' ? $this->csv($entetes, $lignes) : $this->excel->raw(new TableauExport($entetes, $lignes), Excel::XLSX);
    }

    public function nomDeFichier(string $base, string $format): string
    {
        return $base.'-'.now()->format('Y-m-d').'.'.$format;
    }

    /** Semicolon and BOM: what a French-locale Excel needs to split columns and read accents. */
    private function csv(array $entetes, array $lignes): string
    {
        $flux = fopen('php://temp', 'r+');
        foreach ([$entetes, ...$lignes] as $ligne) {
            fputcsv($flux, $ligne, ';', '"', '\\');
        }
        rewind($flux);

        return "\xEF\xBB\xBF".stream_get_contents($flux);
    }

    /** A text cell starting with = + - @ would run as a formula when opened (CSV/formula injection). */
    private function neutraliser(mixed $cellule): mixed
    {
        return is_string($cellule) && $cellule !== '' && str_contains('=+-@', $cellule[0]) ? "'".$cellule : $cellule;
    }
}
