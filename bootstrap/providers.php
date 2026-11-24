<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\EcolePanelProvider;

// Bounded contexts are modules (app-modules/*) whose providers Laravel discovers through Composer,
// before these ones: their Panel::configureUsing callbacks exist when the panel is built.
return [
    AppServiceProvider::class,
    EcolePanelProvider::class,
];
