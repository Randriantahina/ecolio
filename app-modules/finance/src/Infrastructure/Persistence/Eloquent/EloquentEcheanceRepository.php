<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Models\Echeance;
use Modules\Finance\Domain\Models\PaiementLigne;

final class EloquentEcheanceRepository implements EcheanceRepository
{
    public function save(Echeance $echeance): void
    {
        $echeance->save();
    }

    public function exists(int $inscriptionId, int $typeFraisId, ?string $periode): bool
    {
        return Echeance::query()->where('inscription_id', $inscriptionId)->where('type_frais_id', $typeFraisId)
            ->when($periode === null, fn ($q) => $q->whereNull('periode'), fn ($q) => $q->where('periode', $periode))
            ->exists();
    }

    public function lockByUuids(array $uuids, int $ecoleId): Collection
    {
        return Echeance::query()->where('ecole_id', $ecoleId)->whereIn('uuid', $uuids)->orderBy('id')->lockForUpdate()->get();
    }

    public function paidAmount(int $echeanceId): int
    {
        return (int) PaiementLigne::query()->where('echeance_id', $echeanceId)
            ->whereHas('paiement', fn ($p) => $p->where('statut', 'valide'))->sum('montant_ar');
    }

    public function resteAPayerTotal(int $ecoleId): int
    {
        $du = (int) Echeance::query()->where('ecole_id', $ecoleId)->where('statut', '!=', 'annule')
            ->selectRaw('coalesce(sum(montant_ar - remise_ar), 0) as total')->value('total');

        // Capped per instalment would need a join; valid lines can never exceed the net (checked at payment time).
        $paye = (int) PaiementLigne::query()
            ->whereHas('paiement', fn ($p) => $p->where('statut', 'valide'))
            ->whereHas('echeance', fn ($e) => $e->where('ecole_id', $ecoleId)->where('statut', '!=', 'annule'))
            ->sum('montant_ar');

        return max(0, $du - $paye);
    }

    public function countParStatut(int $ecoleId): array
    {
        return Echeance::query()->where('ecole_id', $ecoleId)->selectRaw('statut, count(*) as total')->groupBy('statut')
            ->pluck('total', 'statut')->map(fn ($v): int => (int) $v)->all();
    }

    public function countImpayees(int $ecoleId): int
    {
        return Echeance::query()->where('ecole_id', $ecoleId)->whereIn('statut', ['du', 'partiel'])->count();
    }

    public function paidAmounts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return PaiementLigne::query()->whereIn('echeance_id', $ids)
            ->whereHas('paiement', fn ($p) => $p->where('statut', 'valide'))
            ->selectRaw('echeance_id, sum(montant_ar) as total')->groupBy('echeance_id')
            ->pluck('total', 'echeance_id')->map(fn ($v) => (int) $v)->all();
    }

    public function lastReceiptNumbers(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return PaiementLigne::query()->whereIn('echeance_id', $ids)
            ->whereHas('paiement', fn ($p) => $p->where('statut', 'valide'))
            ->with('paiement.recu')->orderBy('id')->get()
            ->filter(fn (PaiementLigne $l) => $l->paiement->recu !== null)
            ->mapWithKeys(fn (PaiementLigne $l) => [$l->echeance_id => [
                'numero' => $l->paiement->recu->numero_affiche,
                'paiement_uuid' => $l->paiement->uuid,
            ]])->all();
    }

    public function cancelFutureUnpaid(int $eleveId, string $moisSortie): int
    {
        // Monthly periods only (`YYYY-MM`): one-off and quarterly fees have no "after" month to compare.
        return Echeance::query()->where('eleve_id', $eleveId)->where('statut', 'du')
            ->whereNotNull('periode')->where('periode', 'like', '____-__')->where('periode', '>', $moisSortie)
            ->update(['statut' => 'annule']);
    }

    public function impayees(int $ecoleId): Collection
    {
        return Echeance::query()->with(['typeFrais', 'eleve.inscriptionActive.classe'])
            ->where('ecole_id', $ecoleId)->whereIn('statut', ['du', 'partiel'])->get()
            ->sortBy(fn (Echeance $e) => $e->eleve->matricule.'|'.$e->id)->values();
    }

    public function forEleve(int $eleveId): Collection
    {
        return Echeance::query()->with('typeFrais')->where('eleve_id', $eleveId)->orderBy('periode')->orderBy('id')->get();
    }

    public function forClassePeriode(int $ecoleId, int $classeId, ?string $periode, ?bool $ponctuels = null): Collection
    {
        return Echeance::query()->with(['typeFrais', 'eleve'])
            ->where('ecole_id', $ecoleId)
            ->whereHas('eleve.inscriptionActive', fn ($i) => $i->where('classe_id', $classeId))
            ->when($periode !== null, fn ($q) => $q->where('periode', $periode))
            ->when($ponctuels !== null, fn ($q) => $q->whereHas('typeFrais', fn ($t) => $ponctuels
                ? $t->whereIn('periodicite', ['unique', 'annuel'])
                : $t->whereNotIn('periodicite', ['unique', 'annuel'])))
            ->orderBy('eleve_id')->orderBy('id')->get();
    }
}
