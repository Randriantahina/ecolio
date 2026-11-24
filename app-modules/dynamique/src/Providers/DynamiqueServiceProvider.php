<?php

declare(strict_types=1);

namespace Modules\Dynamique\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Dynamique\Application\Services\ChampService;
use Modules\Dynamique\Domain\Contracts\ChampRepository;
use Modules\Dynamique\Domain\Models\ChampPersonnalise;
use Modules\Dynamique\DynamiquePlugin;
use Modules\Dynamique\Infrastructure\Persistence\Eloquent\EloquentChampRepository;
use Modules\Dynamique\Policies\ChampPersonnalisePolicy;

final class DynamiqueServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        ChampRepository::class => EloquentChampRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(DynamiquePlugin::make());
            }
        });

        // Modules owning an entity tag their LecteurValeursChamps with 'champs.lecteurs' (RG-D3 type checks).
        $this->app->when(ChampService::class)->needs('$lecteurs')->giveTagged('champs.lecteurs');
    }

    public function boot(): void
    {
        Gate::policy(ChampPersonnalise::class, ChampPersonnalisePolicy::class);
    }
}
