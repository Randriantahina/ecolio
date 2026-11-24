<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Enums;

/** Which table a saved view applies to. */
enum EntiteVue: string
{
    case Eleve = 'eleve';
    case Paiement = 'paiement';
    case Absence = 'absence';
}
