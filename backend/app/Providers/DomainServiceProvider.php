<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;
use App\Domain\Platform\Contracts\EcoleRepository;
use App\Domain\Platform\Models\Ecole;
use App\Infrastructure\Persistence\Eloquent\EloquentEcoleRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use App\Policies\EcolePolicy;
use App\Policies\UtilisateurPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Single place where interfaces meet their implementations (Dependency Inversion) and policies are declared. */
final class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
        EcoleRepository::class => EloquentEcoleRepository::class,
    ];

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Utilisateur::class, UtilisateurPolicy::class);
        Gate::policy(Ecole::class, EcolePolicy::class);
    }
}
