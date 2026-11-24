<?php

declare(strict_types=1);

namespace Modules\Absences\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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

    public function register(): void {}

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Absence::class, AbsencePolicy::class);
    }
}
