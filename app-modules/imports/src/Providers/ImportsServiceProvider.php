<?php

declare(strict_types=1);

namespace Modules\Imports\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Imports\Domain\Contracts\ImportRepository;
use Modules\Imports\Domain\Contracts\LecteurFichier;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\ImportsPlugin;
use Modules\Imports\Infrastructure\Excel\PhpSpreadsheetLecteurFichier;
use Modules\Imports\Infrastructure\Persistence\Eloquent\EloquentImportRepository;
use Modules\Imports\Policies\ImportPolicy;

final class ImportsServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        LecteurFichier::class => PhpSpreadsheetLecteurFichier::class,
        ImportRepository::class => EloquentImportRepository::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin to the school panel.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(ImportsPlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find this one.
        Gate::policy(Import::class, ImportPolicy::class);
    }
}
