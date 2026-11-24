<?php

declare(strict_types=1);

namespace Modules\Eleves\Domain\Enums;

/** RG-E2: a pupil who leaves is never deleted, only moved to another status. */
enum StatutEleve: string
{
    case Actif = 'actif';
    case Sorti = 'sorti';
    case Transfere = 'transfere';
    case Abandon = 'abandon';
    case Diplome = 'diplome';

    public function libelle(): string
    {
        return match ($this) {
            self::Actif => 'Actif',
            self::Sorti => 'Sorti',
            self::Transfere => 'Transféré',
            self::Abandon => 'Abandon',
            self::Diplome => 'Diplômé',
        };
    }
}
