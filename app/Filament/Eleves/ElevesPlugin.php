<?php

declare(strict_types=1);

namespace App\Filament\Eleves;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Eleves": the pupil registry. Discovery keeps the panel provider untouched. */
final class ElevesPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'eleves';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__.'/Resources', for: 'App\\Filament\\Eleves\\Resources');
    }

    public function boot(Panel $panel): void {}
}
