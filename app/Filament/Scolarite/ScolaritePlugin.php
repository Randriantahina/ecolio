<?php

declare(strict_types=1);

namespace App\Filament\Scolarite;

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
        $panel->discoverResources(in: __DIR__.'/Resources', for: 'App\\Filament\\Scolarite\\Resources');
    }

    public function boot(Panel $panel): void {}
}
