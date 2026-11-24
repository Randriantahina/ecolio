<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Filament\Panel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\EnvoyeurSms;
use Modules\Identity\Domain\Contracts\UtilisateurRepository;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\IdentityPlugin;
use Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUtilisateurRepository;
use Modules\Identity\Infrastructure\Sms\JournalEnvoyeurSms;
use Modules\Identity\Policies\RolePolicy;
use Modules\Identity\Policies\UtilisateurPolicy;
use Spatie\Permission\Models\Role;

final class IdentityServiceProvider extends ServiceProvider
{
    /** Dependency Inversion: the module's interfaces meet their implementations here. */
    public array $bindings = [
        UtilisateurRepository::class => EloquentUtilisateurRepository::class,
        EnvoyeurSms::class => JournalEnvoyeurSms::class,
    ];

    public function register(): void
    {
        // Filament modular architecture: the module attaches its own plugin to the school panel.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'ecole') {
                $panel->plugin(IdentityPlugin::make());
            }
        });
    }

    public function boot(): void
    {
        // Models live under Domain/, so Laravel's name-based policy discovery cannot find these.
        Gate::policy(Utilisateur::class, UtilisateurPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        ResetPassword::createUrlUsing(fn ($user, string $token) => config('app.frontend_url')
            .'/reinitialiser-mot-de-passe?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
    }
}
