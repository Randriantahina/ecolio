<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The web UI is the Filament panel; the root only points staff to it.
Route::redirect('/', '/ecole');
