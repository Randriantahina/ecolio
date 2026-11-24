<?php

declare(strict_types=1);

namespace Modules\Identity\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;

/** Shield's roles page, kept out of tenant scoping: a role row has no `ecole` relation. */
final class RoleResource extends ShieldRoleResource
{
    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 20;
}
