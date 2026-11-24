<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Finance\Domain\Contracts\PaiementRepository;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Domain\Models\PaiementLigne;
use Modules\Finance\Domain\Models\Recu;

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

    public function totalEncaisse(int $ecoleId, string $from, string $to): int
    {
        return (int) Paiement::query()->where('ecole_id', $ecoleId)->where('statut', 'valide')
            ->whereBetween('date_paiement', [$from, $to])->sum('total_ar');
    }

    public function totalEncaisseParMois(int $ecoleId, string $from, string $to): array
    {
        // Grouped in PHP: strftime/to_char differ between SQLite (tests) and PostgreSQL (prod).
        return Paiement::query()->where('ecole_id', $ecoleId)->where('statut', 'valide')
            ->whereBetween('date_paiement', [$from, $to])->get(['date_paiement', 'total_ar'])
            ->groupBy(fn (Paiement $p): string => $p->date_paiement->format('Y-m'))
            ->map(fn ($g): int => (int) $g->sum('total_ar'))->all();
    }

    public function forEleve(int $eleveId): Collection
    {
        return Paiement::query()->with(['recu'])->where('eleve_id', $eleveId)->orderByDesc('date_paiement')->orderByDesc('id')->get();
    }
}
