<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs');
            $table->string('entite', 20);
            $table->string('mode', 20);
            $table->string('fichier_path');
            $table->string('nom_original');
            $table->string('feuille')->nullable();
            $table->unsignedInteger('ligne_entete')->default(1);
            $table->json('mapping')->nullable();
            $table->string('statut', 20);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('crees')->default(0);
            $table->unsignedInteger('mis_a_jour')->default(0);
            $table->unsignedInteger('ignores')->default(0);
            $table->unsignedInteger('en_erreur')->default(0);
            $table->json('rapport')->nullable();
            $table->timestamp('termine_le')->nullable();
            $table->timestamps();

            $table->index(['ecole_id', 'statut']);
        });

        // RG-M3: nothing touches the business tables before the final step; every row waits here.
        Schema::create('import_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->json('donnees');
            $table->json('erreurs')->nullable();
            $table->string('statut', 20);
            $table->boolean('confirmee')->default(false);
            $table->string('action', 20)->nullable();
            $table->unsignedBigInteger('enregistrement_id')->nullable();
            $table->json('avant')->nullable();
            $table->timestamps();

            $table->index(['import_id', 'statut']);
            $table->unique(['import_id', 'numero']);
        });

        // "The mapping is remembered for the next imports of the same school" (spec 4.1 step 2).
        Schema::create('import_correspondances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('entite', 20);
            $table->string('entete');
            $table->string('cible');
            $table->timestamps();

            $table->unique(['ecole_id', 'entite', 'entete']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_correspondances');
        Schema::dropIfExists('import_lignes');
        Schema::dropIfExists('imports');
    }
};
