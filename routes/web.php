<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AccueilController;
use App\Http\Controllers\Web\ConnexionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [ConnexionController::class, 'show'])->name('connexion');
    Route::post('/connexion', [ConnexionController::class, 'store']);
});

// `tenant` runs after `auth`: the school always comes from the signed-in user, never from the URL.
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/deconnexion', [ConnexionController::class, 'destroy'])->name('deconnexion');
    Route::get('/', AccueilController::class)->name('accueil');
});
