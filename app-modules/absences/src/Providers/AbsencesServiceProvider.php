<?php

declare(strict_types=1);

namespace Modules\Absences\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Absences\AbsencesPlugin;
use Modules\Absences\Domain\Contracts\AbsenceRepository;
use Modules\Absences\Domain\Models\Absence;
use Modules\Absences\Infrastructure\Persistence\Eloquent\EloquentAbsenceRepository;
use Modules\Absences\Policies\AbsencePolicy;

final class AbsencesServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        AbsenceRepository::class => EloquentAbsenceRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(AbsencesPlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Absence::class, AbsencePolicy::class);
    }
}
