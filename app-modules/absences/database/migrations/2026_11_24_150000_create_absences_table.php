<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes');
            $table->date('date');
            $table->string('periode')->default('journee');
            $table->boolean('justifiee')->default(false);
            $table->string('motif')->nullable();
            // Idempotency key for offline entry: a replayed batch must not duplicate an absence.
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('saisi_par')->constrained('utilisateurs');
            $table->timestamps();

            // RG-P5: one absence per pupil, day and period.
            $table->unique(['eleve_id', 'date', 'periode']);
            $table->index(['ecole_id', 'classe_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
