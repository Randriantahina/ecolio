<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Contracts;

use Modules\Imports\Application\DTOs\LectureFichier;
use Modules\Imports\Domain\Exceptions\FichierIllisibleException;

interface LecteurFichier
{
    /**
     * @return list<string> a CSV has a single sheet
     *
     * @throws FichierIllisibleException
     */
    public function feuilles(string $chemin): array;

    /**
     * @param  ?string  $feuille  null = the first sheet
     *
     * @throws FichierIllisibleException
     */
    public function lire(string $chemin, ?string $feuille = null): LectureFichier;
}
