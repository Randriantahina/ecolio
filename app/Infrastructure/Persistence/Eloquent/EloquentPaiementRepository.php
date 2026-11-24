<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Finance\Contracts\PaiementRepository;
use App\Domain\Finance\Models\Paiement;
use App\Domain\Finance\Models\PaiementLigne;
use App\Domain\Finance\Models\Recu;
use Illuminate\Support\Collection;

final class EloquentPaiementRepository implements PaiementRepository
{
    private const WITH = ['recu', 'lignes.echeance', 'eleve.inscriptionActive.classe'];

    public function save(Paiement $paiement): void
    {
        $paiement->save();
    }

    public function saveLignes(Paiement $paiement, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            PaiementLigne::create(['paiement_id' => $paiement->id] + $ligne);
        }
    }

    public function saveRecu(Recu $recu): void
    {
        $recu->save();
    }

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Paiement
    {
        return Paiement::query()->with(self::WITH)->where('ecole_id', $ecoleId)->where('client_uuid', $clientUuid)->first();
    }

    public function findOrFail(string $uuid, int $ecoleId): Paiement
    {
        return Paiement::query()->with(self::WITH)->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function findForUpdateOrFail(string $uuid, int $ecoleId): Paiement
    {
        return Paiement::query()->where('ecole_id', $ecoleId)->where('uuid', $uuid)->lockForUpdate()->firstOrFail();
    }

    public function forEleve(int $eleveId): Collection
    {
        return Paiement::query()->with(['recu'])->where('eleve_id', $eleveId)->orderByDesc('date_paiement')->orderByDesc('id')->get();
    }
}
