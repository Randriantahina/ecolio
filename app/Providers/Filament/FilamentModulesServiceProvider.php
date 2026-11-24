<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Eleves\ElevesPlugin;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

/**
 * One plugin per bounded context, attached to the school panel without touching its provider
 * (Filament modular architecture guide). Add a context = add one line here.
 */
final class FilamentModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'ecole') {
                return;
            }

            $panel->plugins([ElevesPlugin::make()]);
        });
    }
}
