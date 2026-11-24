<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Domain\Contracts\JournalAuditRepository;
use Modules\Audit\Domain\Models\JournalAudit;
use Modules\Audit\Infrastructure\Persistence\Eloquent\EloquentJournalAuditRepository;
use Modules\Audit\Policies\JournalAuditPolicy;

final class AuditServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        JournalAuditRepository::class => EloquentJournalAuditRepository::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(JournalAudit::class, JournalAuditPolicy::class);
    }
}
