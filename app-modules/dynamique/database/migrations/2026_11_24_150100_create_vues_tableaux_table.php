<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vues_tableaux', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('entite', 20);
            $table->string('nom', 80);
            // Filters, search, sort and column visibility/order of the table at save time.
            $table->json('etat');
            $table->boolean('est_partagee')->default(false);
            $table->boolean('est_par_defaut')->default(false);
            $table->timestamps();

            $table->unique(['ecole_id', 'utilisateur_id', 'entite', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vues_tableaux');
    }
};
