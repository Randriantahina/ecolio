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
        Schema::create('types_frais', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('nom');
            $table->string('periodicite');
            $table->boolean('est_obligatoire')->default(true);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['ecole_id', 'nom']);
        });

        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('type_frais_id')->constrained('types_frais')->cascadeOnDelete();
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->unsignedInteger('montant_ar');
            $table->timestamps();
        });
        // NULLs are distinct in a plain unique index, so uniqueness is spelled out per scope.
        DB::statement('CREATE UNIQUE INDEX tarifs_unique_niveau ON tarifs (annee_scolaire_id, type_frais_id, niveau_id) WHERE classe_id IS NULL AND niveau_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX tarifs_unique_classe ON tarifs (annee_scolaire_id, type_frais_id, classe_id) WHERE classe_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX tarifs_unique_ecole ON tarifs (annee_scolaire_id, type_frais_id) WHERE classe_id IS NULL AND niveau_id IS NULL');

        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('type_frais_id')->constrained('types_frais');
            // "2026-09" for monthly, "2026-T1" for quarterly, null for one-off/annual fees.
            $table->string('periode', 7)->nullable();
            $table->string('libelle');
            $table->unsignedInteger('montant_ar');
            $table->unsignedInteger('remise_ar')->default(0);
            $table->string('remise_motif')->nullable();
            $table->date('date_limite')->nullable();
            $table->string('statut')->default('du');
            $table->timestamps();

            $table->index(['ecole_id', 'statut', 'periode']);
        });
        // UQ(inscription, type, periode): the same expression guards the NULL-period case.
        DB::statement('CREATE UNIQUE INDEX echeances_unique_periode ON echeances (inscription_id, type_frais_id, periode) WHERE periode IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX echeances_unique_sans_periode ON echeances (inscription_id, type_frais_id) WHERE periode IS NULL');

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves');
            $table->date('date_paiement');
            $table->unsignedInteger('total_ar');
            $table->string('mode');
            $table->string('reference_externe')->nullable();
            // Idempotency key for offline replays: a retry must never create a second payment.
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('created_by')->constrained('utilisateurs');
            $table->string('statut')->default('valide');
            $table->timestamp('annule_le')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('utilisateurs');
            $table->string('motif_annulation')->nullable();
            $table->timestamps();

            $table->index(['ecole_id', 'date_paiement']);
        });
        DB::statement('ALTER TABLE paiements ADD CONSTRAINT paiements_total_positif CHECK (total_ar > 0)');

        Schema::create('paiement_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->cascadeOnDelete();
            $table->foreignId('echeance_id')->constrained('echeances');
            $table->unsignedInteger('montant_ar');
        });
        DB::statement('ALTER TABLE paiement_lignes ADD CONSTRAINT paiement_lignes_montant_positif CHECK (montant_ar > 0)');

        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('paiement_id')->unique()->constrained('paiements');
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('numero');
            $table->string('numero_affiche', 20);
            $table->timestamp('emis_le');
            $table->string('pdf_path')->nullable();

            // RG-R1: the database refuses a duplicate receipt number even if the counter were bypassed.
            $table->unique(['ecole_id', 'annee', 'numero']);
        });
    }

    public function down(): void
    {
        foreach (['recus', 'paiement_lignes', 'paiements', 'echeances', 'tarifs', 'types_frais'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
