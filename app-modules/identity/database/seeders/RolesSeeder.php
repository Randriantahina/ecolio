<?php

declare(strict_types=1);

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Domain\Enums\Role;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

final class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate keeps deploys idempotent; the cache reset avoids stale role lookups after reseeding.
        foreach (Role::cases() as $role) {
            RoleModel::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
