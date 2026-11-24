<?php

declare(strict_types=1);

namespace Modules\Eleves\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Eleves\Domain\Models\Eleve;

final class EloquentEleveRepository implements EleveRepository
{
    public function paginate(int $ecoleId, array $filtres, int $perPage): CursorPaginator
    {
        $query = Eleve::query()
            ->with('inscriptionActive.classe.niveau')
            ->where('ecole_id', $ecoleId);

        if (($q = trim((string) ($filtres['q'] ?? ''))) !== '') {
            // lower() on both sides: LIKE is case-insensitive on SQLite but not on PostgreSQL.
            $needle = '%'.mb_strtolower($q).'%';
            $query->where(fn ($w) => $w
                ->whereRaw('lower(matricule) like ?', [$needle])
                ->orWhereRaw('lower(nom) like ?', [$needle])
                ->orWhereRaw('lower(coalesce(prenoms, \'\')) like ?', [$needle]));
        }

        if (! empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        if (! empty($filtres['classe'])) {
            $query->whereHas('inscriptionActive.classe', fn ($c) => $c->where('uuid', $filtres['classe']));
        }

        return $query->orderBy('matricule')->orderBy('id')->cursorPaginate($perPage);
    }

    public function findOrFail(string $uuid, int $ecoleId): Eleve
    {
        return Eleve::query()->with('inscriptionActive.classe.niveau')
            ->where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function effectifs(int $ecoleId): array
    {
        $actifs = Eleve::query()->where('ecole_id', $ecoleId)->where('statut', 'actif');

        return [
            'actifs' => (clone $actifs)->count(),
            'sans_classe' => (clone $actifs)->whereDoesntHave('inscriptionActive')->count(),
        ];
    }

    public function save(Eleve $eleve): void
    {
        $eleve->save();
    }

    public function matriculeExists(int $ecoleId, string $matricule, ?int $sauf = null): bool
    {
        return Eleve::withTrashed()->where('ecole_id', $ecoleId)->where('matricule', $matricule)
            ->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))->exists();
    }

    public function findDuplicate(int $ecoleId, string $nom, ?string $prenoms, ?string $dateNaissance): ?Eleve
    {
        if ($dateNaissance === null) {
            return null; // without a birth date a name match alone would flag every namesake
        }

        return Eleve::query()->where('ecole_id', $ecoleId)
            ->whereRaw('lower(nom) = ?', [mb_strtolower($nom)])
            ->whereRaw('lower(coalesce(prenoms, \'\')) = ?', [mb_strtolower($prenoms ?? '')])
            ->whereDate('date_naissance', $dateNaissance)
            ->first();
    }
}
