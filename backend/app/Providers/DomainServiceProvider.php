<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Contracts\UtilisateurRepository;
use App\Domain\Identity\Models\Utilisateur;
use App\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use App\Policies\UtilisateurPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/** Single place where interfaces meet their implementations (Dependency Inversion) and policies are declared. */
final class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
    ];

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Utilisateur::class, UtilisateurPolicy::class);
    }
}
