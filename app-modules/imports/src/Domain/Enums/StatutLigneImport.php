<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Enums;

enum StatutLigneImport: string
{
    case Ok = 'ok';
    case Erreur = 'erreur';
    case Exclue = 'exclue';
    case Importee = 'importee';
}
