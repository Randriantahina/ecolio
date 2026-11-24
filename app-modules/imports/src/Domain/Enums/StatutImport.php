<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Enums;

enum StatutImport: string
{
    case Envoye = 'envoye';
    case Mappe = 'mappe';
    case Valide = 'valide';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Echec = 'echec';
    case Annule = 'annule';
}
