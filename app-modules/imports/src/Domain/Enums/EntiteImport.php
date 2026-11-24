<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Enums;

enum EntiteImport: string
{
    case Eleves = 'eleves';
    case Tuteurs = 'tuteurs';
    case Paiements = 'paiements';

    public function libelle(): string
    {
        return match ($this) {
            self::Eleves => 'Élèves',
            self::Tuteurs => 'Tuteurs',
            self::Paiements => 'Paiements historiques',
        };
    }
}
