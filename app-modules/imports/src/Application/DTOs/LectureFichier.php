<?php

declare(strict_types=1);

namespace Modules\Imports\Application\DTOs;

/** What a spreadsheet sheet contains, plus what the reader noticed but refused to guess (RG-M4). */
final readonly class LectureFichier
{
    /**
     * @param  array<int, list<scalar|null>>  $lignes  keyed by the real row number of the file
     * @param  list<string>  $avertissements
     */
    public function __construct(public array $lignes, public array $avertissements = []) {}
}
