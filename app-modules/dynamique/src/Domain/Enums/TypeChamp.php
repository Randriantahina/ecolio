<?php

declare(strict_types=1);

namespace Modules\Dynamique\Domain\Enums;

enum TypeChamp: string
{
    case Texte = 'texte';
    case Nombre = 'nombre';
    case Date = 'date';
    case Booleen = 'booleen';
    case Liste = 'liste';
    case Telephone = 'telephone';
}
