<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/** Spec section 1. Values are the spatie role names stored in the `roles` table. */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Directeur = 'directeur';
    case Secretaire = 'secretaire';
    case Enseignant = 'enseignant';
    case Parent = 'parent';
}
