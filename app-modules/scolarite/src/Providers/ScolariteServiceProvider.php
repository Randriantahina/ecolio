<?php

declare(strict_types=1);

namespace Modules\Scolarite\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Scolarite\Domain\Contracts\AnneeScolaireRepository;
use Modules\Scolarite\Domain\Contracts\StructureRepository;
use Modules\Scolarite\Domain\Models\AnneeScolaire;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;
use Modules\Scolarite\Infrastructure\Persistence\Eloquent\EloquentAnneeScolaireRepository;
use Modules\Scolarite\Infrastructure\Persistence\Eloquent\EloquentStructureRepository;
use Modules\Scolarite\Policies\AnneeScolairePolicy;
use Modules\Scolarite\ScolaritePlugin;

final class ScolariteServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        AnneeScolaireRepository::class => EloquentAnneeScolaireRepository::class,
        StructureRepository::class => EloquentStructureRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(ScolaritePlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        // Calendar, levels and classes share one rule: staff read, the directeur writes (spec section 1).
        Gate::policy(AnneeScolaire::class, AnneeScolairePolicy::class);
        Gate::policy(Niveau::class, AnneeScolairePolicy::class);
        Gate::policy(Classe::class, AnneeScolairePolicy::class);
    }
}
