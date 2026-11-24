<?php

use App\Providers\AppServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\Filament\EcolePanelProvider;
use App\Providers\Filament\FilamentModulesServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    // Before the panel provider: its configureUsing callback must exist when the panel is built.
    FilamentModulesServiceProvider::class,
    EcolePanelProvider::class,
];
