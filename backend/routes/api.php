<?php

declare(strict_types=1);

use App\Http\Controllers\Absences\AbsenceController;
use App\Http\Controllers\Audit\AuditController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Ecole\EcoleController;
use App\Http\Controllers\Eleves\EleveController;
use App\Http\Controllers\Finance\FraisController;
use App\Http\Controllers\Finance\PaiementController;
use App\Http\Controllers\Scolarite\AnneeScolaireController;
use App\Http\Controllers\Scolarite\StructureController;
use App\Http\Controllers\Team\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('auth/password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('auth/password/reset', [PasswordController::class, 'reset'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('audit', [AuditController::class, 'index']);

        Route::get('ecole', [EcoleController::class, 'show']);
        Route::put('ecole', [EcoleController::class, 'update']);

        Route::get('annees-scolaires', [AnneeScolaireController::class, 'index']);
        Route::post('annees-scolaires', [AnneeScolaireController::class, 'store']);
        Route::patch('annees-scolaires/{uuid}', [AnneeScolaireController::class, 'update']);
        Route::post('annees-scolaires/{uuid}/courante', [AnneeScolaireController::class, 'definirCourante']);

        Route::get('eleves', [EleveController::class, 'index']);
        Route::post('eleves', [EleveController::class, 'store']);
        Route::get('eleves/{uuid}', [EleveController::class, 'show']);
        Route::patch('eleves/{uuid}', [EleveController::class, 'update']);
        Route::post('eleves/{uuid}/inscriptions', [EleveController::class, 'inscrire']);
        Route::post('eleves/{uuid}/sortie', [EleveController::class, 'sortie']);
        Route::patch('eleves/{uuid}/classe', [EleveController::class, 'changerClasse']);

        Route::get('types-frais', [FraisController::class, 'types']);
        Route::post('types-frais', [FraisController::class, 'storeType']);
        Route::patch('types-frais/{uuid}', [FraisController::class, 'updateType']);
        Route::get('tarifs', [FraisController::class, 'tarifs']);
        Route::post('tarifs', [FraisController::class, 'storeTarif']);
        Route::patch('tarifs/{uuid}', [FraisController::class, 'updateTarif']);
        Route::delete('tarifs/{uuid}', [FraisController::class, 'destroyTarif']);

        Route::post('paiements', [PaiementController::class, 'store']);
        Route::get('paiements/{uuid}', [PaiementController::class, 'show']);
        Route::post('paiements/{uuid}/annulation', [PaiementController::class, 'annuler']);
        Route::get('eleves/{uuid}/echeances', [PaiementController::class, 'echeancesEleve']);
        Route::get('classes/{uuid}/registre-ecolage', [PaiementController::class, 'registre']);

        Route::get('classes/{uuid}/absences', [AbsenceController::class, 'index']);
        Route::put('classes/{uuid}/absences', [AbsenceController::class, 'saisir']);

        Route::get('niveaux', [StructureController::class, 'niveaux']);
        Route::post('niveaux', [StructureController::class, 'storeNiveau']);
        Route::patch('niveaux/{uuid}', [StructureController::class, 'updateNiveau']);
        Route::get('classes', [StructureController::class, 'classes']);
        Route::post('classes', [StructureController::class, 'storeClasse']);
        Route::patch('classes/{uuid}', [StructureController::class, 'updateClasse']);
        Route::delete('classes/{uuid}', [StructureController::class, 'destroyClasse']);

        Route::get('utilisateurs', [UtilisateurController::class, 'index']);
        Route::post('utilisateurs', [UtilisateurController::class, 'store']);
        Route::patch('utilisateurs/{uuid}', [UtilisateurController::class, 'update']);
    });
});
