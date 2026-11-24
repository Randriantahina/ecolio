<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Enums;

/**
 * Two staff roles only (decision 0006): the administration runs the school, the enseignant takes the
 * roll-call (absences only, later from the mobile app). Fine-grained rights are Shield permissions.
 * Values are the spatie role names stored in the `roles` table.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Administration = 'administration';
    case Enseignant = 'enseignant';
}
