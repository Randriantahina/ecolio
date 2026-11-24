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
        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('est_courante')->default(false);
            $table->timestamps();

            $table->unique(['ecole_id', 'libelle']);
        });

        // RG-S1: the database itself refuses a second current year in a school, so a race between
        // two requests cannot leave two. Partial index syntax is shared by PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX annees_scolaires_une_courante ON annees_scolaires (ecole_id) WHERE est_courante');
    }

    public function down(): void
    {
        Schema::dropIfExists('annees_scolaires');
    }
};
