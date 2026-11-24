<?php

declare(strict_types=1);

namespace Modules\Identity\Policies;

/** The roles page follows the same Shield permissions as every other screen ("ViewAny:Role"...). */
final class RolePolicy extends PolitiqueShield
{
    protected function sujet(): string
    {
        return 'Role';
    }
}
