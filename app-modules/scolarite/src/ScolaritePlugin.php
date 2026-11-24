<?php

declare(strict_types=1);

namespace Modules\Scolarite;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Scolarite": school years, levels and classes. */
final class ScolaritePlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'scolarite';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Scolarite\\Filament\\Resources');
    }

    public function boot(Panel $panel): void {}
}
