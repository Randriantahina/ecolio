<?php

declare(strict_types=1);

namespace Modules\Platform;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Platform": the school itself and its settings cluster. */
final class PlatformPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'platform';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverClusters(in: __DIR__.'/Filament/Clusters', for: 'Modules\\Platform\\Filament\\Clusters');
    }

    public function boot(Panel $panel): void {}
}
