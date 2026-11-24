<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            // e.g. "recu:2026" or "matricule": one independent sequence per key.
            $table->string('cle');
            $table->unsignedBigInteger('valeur')->default(0);

            // Composite on purpose (spec 6.3): two schools both own a "recu:2026" sequence.
            $table->unique(['ecole_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compteurs');
    }
};
