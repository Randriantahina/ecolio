<?php

declare(strict_types=1);

namespace Modules\Dynamique\Infrastructure\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** @internal one sheet: bold headings then the rows */
final class TableauExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @param  list<string>  $entetes
     * @param  list<list<scalar|null>>  $lignes
     */
    public function __construct(private readonly array $entetes, private readonly array $lignes) {}

    public function array(): array
    {
        return $this->lignes;
    }

    public function headings(): array
    {
        return $this->entetes;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
