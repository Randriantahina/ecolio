<?php

declare(strict_types=1);

namespace Modules\Platform\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Platform\Domain\Contracts\EcoleRepository;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Platform\Infrastructure\Persistence\Eloquent\EloquentEcoleRepository;
use Modules\Platform\PlatformPlugin;
use Modules\Platform\Policies\EcolePolicy;

final class PlatformServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        EcoleRepository::class => EloquentEcoleRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(PlatformPlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Ecole::class, EcolePolicy::class);
    }
}
