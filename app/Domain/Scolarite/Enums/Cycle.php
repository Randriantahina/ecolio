<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Enums;

enum Cycle: string
{
    case Maternelle = 'maternelle';
    case Primaire = 'primaire';
    case College = 'college';
    case Lycee = 'lycee';
    case Autre = 'autre';
}
