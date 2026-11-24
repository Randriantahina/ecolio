<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('champs_personnalises', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('entite', 20);
            // RG-D2: slug generated at creation, immutable; the label is free to change.
            $table->string('cle', 60);
            $table->string('libelle', 120);
            $table->string('type', 20);
            $table->json('options')->nullable();
            $table->boolean('obligatoire')->default(false);
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamp('desactive_le')->nullable();
            $table->timestamps();

            $table->unique(['ecole_id', 'entite', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('champs_personnalises');
    }
};
