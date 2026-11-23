<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Eleves\Contracts\InscriptionRepository;
use App\Domain\Eleves\Models\Inscription;
use Illuminate\Support\Facades\DB;

final class EloquentInscriptionRepository implements InscriptionRepository
{
    public function activeFor(int $eleveId, int $anneeId): ?Inscription
    {
        return Inscription::query()->where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)->where('statut', 'active')->first();
    }

    public function save(Inscription $inscription): void
    {
        $inscription->save();
    }

    public function countActiveInClasse(int $classeId): int
    {
        return Inscription::query()->where('classe_id', $classeId)->where('statut', 'active')->count();
    }

    public function recordClassChange(int $ecoleId, int $inscriptionId, int $ancienneClasseId, int $nouvelleClasseId, ?int $parUtilisateurId): void
    {
        DB::table('historique_classes')->insert([
            'ecole_id' => $ecoleId,
            'inscription_id' => $inscriptionId,
            'ancienne_classe_id' => $ancienneClasseId,
            'nouvelle_classe_id' => $nouvelleClasseId,
            'date_effet' => now()->toDateString(),
            'par_utilisateur_id' => $parUtilisateurId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
