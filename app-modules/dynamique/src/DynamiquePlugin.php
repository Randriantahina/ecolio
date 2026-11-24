<?php

declare(strict_types=1);

namespace Modules\Dynamique;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Dynamique": custom columns and saved views (spec sections 3 and M3). */
final class DynamiquePlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'dynamique';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Dynamique\\Filament\\Resources');
    }

    public function boot(Panel $panel): void {}
}
