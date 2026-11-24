<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Enums;

enum ModeImport: string
{
    /** New records only: an existing matricule is an error. */
    case Creation = 'creation';
    /** RG-M2: match by matricule, else by identity with confirmation; unmatched rows are created. */
    case MiseAJour = 'mise_a_jour';
}
