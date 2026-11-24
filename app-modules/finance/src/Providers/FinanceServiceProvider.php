<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Domain\Contracts\CompteurRepository;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Contracts\FraisRepository;
use Modules\Finance\Domain\Contracts\PaiementRepository;
use Modules\Finance\Domain\Contracts\RecuPdfGenerator;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Domain\Models\Tarif;
use Modules\Finance\Domain\Models\TypeFrais;
use Modules\Finance\FinancePlugin;
use Modules\Finance\Infrastructure\Pdf\DompdfRecuPdfGenerator;
use Modules\Finance\Infrastructure\Persistence\Eloquent\EloquentCompteurRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\EloquentEcheanceRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\EloquentFraisRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\EloquentPaiementRepository;
use Modules\Finance\Policies\PaiementPolicy;
use Modules\Finance\Policies\TarifPolicy;
use Modules\Finance\Policies\TypeFraisPolicy;

final class FinanceServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        CompteurRepository::class => EloquentCompteurRepository::class,
        FraisRepository::class => EloquentFraisRepository::class,
        EcheanceRepository::class => EloquentEcheanceRepository::class,
        RecuPdfGenerator::class => DompdfRecuPdfGenerator::class,
        PaiementRepository::class => EloquentPaiementRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin, the panel provider is never edited.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(FinancePlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Paiement::class, PaiementPolicy::class);
        Gate::policy(TypeFrais::class, TypeFraisPolicy::class);
        Gate::policy(Tarif::class, TarifPolicy::class);
    }
}
