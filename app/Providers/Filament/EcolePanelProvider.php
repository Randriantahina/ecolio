<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Identity\Filament\Pages\Login;
use Modules\Platform\Domain\Models\Ecole;
use Modules\Tenancy\Http\Middleware\BindEcoleContext;

/**
 * The school panel. Bounded contexts plug their own pages and resources in through a Filament
 * plugin each (see FilamentModulesServiceProvider), so this class never changes when a context is added.
 */
final class EcolePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('ecole')
            ->path('ecole')
            ->login(Login::class)
            // Filament tenancy, as in the reference project: the school is the tenant, addressed by its slug.
            ->tenant(Ecole::class, slugAttribute: 'slug')
            // Persistent so that Livewire updates (table actions, modals) also run inside the school context.
            ->tenantMiddleware([BindEcoleContext::class], isPersistent: true)
            ->colors(['primary' => Color::Emerald])
            ->brandName('Écolio')
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
