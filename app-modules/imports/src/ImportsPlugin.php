<?php

declare(strict_types=1);

namespace Modules\Imports;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Imports": the Excel import wizard and its history (spec section 4). */
final class ImportsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'imports';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Imports\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/Filament/Pages', for: 'Modules\\Imports\\Filament\\Pages');
    }

    public function boot(Panel $panel): void {}
}
