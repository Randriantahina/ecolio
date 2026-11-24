<?php

declare(strict_types=1);

namespace Modules\Finance;

use Filament\Contracts\Plugin;
use Filament\Panel;

/** Bounded context "Finance": fee types, tariffs, the tuition register, payments and receipts. */
final class FinancePlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'finance';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Finance\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/Filament/Pages', for: 'Modules\\Finance\\Filament\\Pages');
    }

    public function boot(Panel $panel): void {}
}
