<?php

declare(strict_types=1);

namespace Modules\Eleves\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Eleves\Domain\Contracts\InscriptionRepository;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\ElevesPlugin;
use Modules\Eleves\Infrastructure\ChampsPersoLecteur;
use Modules\Eleves\Infrastructure\Persistence\Eloquent\EloquentEleveRepository;
use Modules\Eleves\Infrastructure\Persistence\Eloquent\EloquentInscriptionRepository;
use Modules\Eleves\Policies\ElevePolicy;

final class ElevesServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        EleveRepository::class => EloquentEleveRepository::class,
        InscriptionRepository::class => EloquentInscriptionRepository::class,
    ];

    public function register(): void
    {
        $this->app->tag([ChampsPersoLecteur::class], 'champs.lecteurs');

        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(ElevesPlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Eleve::class, ElevePolicy::class);
    }
}
