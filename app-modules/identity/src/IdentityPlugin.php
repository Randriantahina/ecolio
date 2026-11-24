<?php

declare(strict_types=1);

namespace Modules\Identity;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Identity\Filament\Resources\RoleResource;

/** Bounded context "Identity": the team (users page) and the roles page, both in the settings cluster. */
final class IdentityPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'identity';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Identity\\Filament\\Resources');

        // Shield skips its own RoleResource when the panel already holds one named RoleResource: ours,
        // which is not tenant-scoped (roles are not owned by a school).
        $panel->plugin(FilamentShieldPlugin::make());
    }

    public function boot(Panel $panel): void {}
}
