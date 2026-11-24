<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\UtilisateurRepository;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use Modules\Identity\Policies\UtilisateurPolicy;

final class IdentityServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Utilisateur::class, UtilisateurPolicy::class);

        ResetPassword::createUrlUsing(fn ($user, string $token) => config('app.frontend_url')
            .'/reinitialiser-mot-de-passe?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
    }
}
