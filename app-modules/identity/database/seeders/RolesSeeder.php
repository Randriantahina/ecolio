<?php

declare(strict_types=1);

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Domain\Enums\Role;
use Modules\Identity\Infrastructure\Shield\CataloguePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

final class RolesSeeder extends Seeder
{
    /** Subjects an enseignant works with: absences only, the mobile app's single feature (decision 0006). */
    private const SUJETS_ENSEIGNANT = ['Absence'];

    public function run(): void
    {
        // firstOrCreate keeps deploys idempotent; the cache reset avoids stale lookups after reseeding.
        $roles = collect(Role::cases())->mapWithKeys(
            fn (Role $role) => [$role->value => RoleModel::firstOrCreate(['name' => $role->value, 'guard_name' => 'web'])],
        );

        $noms = CataloguePermissions::noms();
        $existantes = Permission::query()->pluck('name')->all();
        $maintenant = now();
        Permission::query()->insert(collect($noms)->diff($existantes)->map(fn (string $nom): array => [
            'name' => $nom, 'guard_name' => 'web', 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ])->values()->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seeded defaults only: once the administration edits a role on the Roles page, reseeding must not
        // silently undo it, so existing grants are left alone and only an empty role gets its defaults.
        $this->donnerParDefaut($roles[Role::Administration->value], $noms);
        $this->donnerParDefaut($roles[Role::Enseignant->value], array_filter(
            $noms,
            fn (string $nom): bool => in_array(explode(':', $nom)[1] ?? '', self::SUJETS_ENSEIGNANT, true),
        ));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param  array<int, string>  $permissions */
    private function donnerParDefaut(RoleModel $role, array $permissions): void
    {
        if ($role->permissions()->doesntExist()) {
            // One bulk insert: syncPermissions would flush the permission cache once per row, ~150 times per seeding.
            $role->permissions()->attach(Permission::query()->whereIn('name', $permissions)->pluck('id')->all());
        }
    }
}
