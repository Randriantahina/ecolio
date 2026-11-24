<?php

declare(strict_types=1);

namespace Modules\Absences;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Absences": the daily roll-call. */
final class AbsencesPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'absences';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverPages(in: __DIR__.'/Filament/Pages', for: 'Modules\\Absences\\Filament\\Pages');
    }

    public function boot(Panel $panel): void {}
}
