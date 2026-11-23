<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->string('cycle')->default('autre');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['ecole_id', 'nom']);
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux');
            $table->string('nom');
            // Nullable and unconstrained until the enseignants table exists (phase 4 prerequisites).
            $table->unsignedBigInteger('titulaire_id')->nullable();
            // RG-I2: a ceiling that warns, never blocks an enrolment.
            $table->unsignedSmallInteger('effectif_max')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ecole_id', 'annee_scolaire_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
        Schema::dropIfExists('niveaux');
    }
};
