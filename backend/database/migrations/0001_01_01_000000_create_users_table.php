<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Spec 7.3 `utilisateurs`: ecole_id null = platform super admin. The "e-mail or phone" CHECK is
        // enforced in the model because SQLite (used by tests) cannot add CHECK constraints after creation.
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->restrictOnDelete();
            $table->string('nom');
            $table->string('email')->nullable()->unique();
            $table->string('telephone')->nullable()->unique();
            $table->string('mot_de_passe');
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('tentatives_echouees')->default(0);
            $table->timestamp('verrouille_jusqua')->nullable();
            $table->timestamp('derniere_connexion')->nullable();
            $table->string('langue', 5)->default('fr');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
