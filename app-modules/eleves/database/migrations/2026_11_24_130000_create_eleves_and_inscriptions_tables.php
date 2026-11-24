<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('matricule', 32);
            $table->string('nom');
            $table->string('prenoms')->nullable();
            $table->string('sexe', 1)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('statut')->default('actif');
            $table->date('date_entree')->nullable();
            $table->date('date_sortie')->nullable();
            $table->string('motif_sortie')->nullable();
            $table->text('commentaire_sortie')->nullable();
            // Custom fields never feed financial or grade computation (spec 4, M5).
            $table->json('champs_perso')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // RG-E1 + spec 6.3: unique per school, never globally.
            $table->unique(['ecole_id', 'matricule']);
            $table->index(['ecole_id', 'nom', 'prenoms']);
            $table->index(['ecole_id', 'statut']);
        });

        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->foreignId('classe_id')->constrained('classes');
            $table->date('date_inscription');
            $table->string('type')->default('nouvelle');
            $table->boolean('est_redoublant')->default(false);
            $table->string('statut')->default('active');
            $table->timestamps();
        });
        // RG-I1: at most one active enrolment per pupil and year, enforced by the database too.
        DB::statement("CREATE UNIQUE INDEX inscriptions_une_active ON inscriptions (eleve_id, annee_scolaire_id) WHERE statut = 'active'");

        Schema::create('historique_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('ancienne_classe_id')->constrained('classes');
            $table->foreignId('nouvelle_classe_id')->constrained('classes');
            $table->date('date_effet');
            $table->foreignId('par_utilisateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_classes');
        Schema::dropIfExists('inscriptions');
        Schema::dropIfExists('eleves');
    }
};
