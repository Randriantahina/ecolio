<?php

declare(strict_types=1);

namespace Modules\Imports\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Imports\Domain\Contracts\ImportRepository;
use Modules\Imports\Domain\Enums\EntiteImport;
use Modules\Imports\Domain\Models\Import;
use Modules\Imports\Domain\Models\ImportCorrespondance;
use Modules\Imports\Domain\Models\ImportLigne;

final class EloquentImportRepository implements ImportRepository
{
    public function findOrFail(string $uuid, int $ecoleId): Import
    {
        return Import::where('ecole_id', $ecoleId)->where('uuid', $uuid)->firstOrFail();
    }

    public function save(Import $import): void
    {
        $import->save();
    }

    public function remplacerLignes(Import $import, array $lignes): void
    {
        DB::transaction(function () use ($import, $lignes): void {
            ImportLigne::where('import_id', $import->id)->delete();
            $maintenant = now();
            // Chunked: SQLite caps the number of bound variables of one statement.
            foreach (array_chunk($lignes, 200) as $lot) {
                ImportLigne::insert(array_map(fn (array $l): array => [
                    'ecole_id' => $import->ecole_id, 'import_id' => $import->id, 'numero' => $l['numero'],
                    'donnees' => json_encode($l['donnees']), 'erreurs' => json_encode($l['erreurs']), 'statut' => $l['statut'],
                    'confirmee' => false, 'action' => $l['action'], 'enregistrement_id' => $l['enregistrement_id'],
                    'created_at' => $maintenant, 'updated_at' => $maintenant,
                ], $lot));
            }
        });
    }

    public function lignes(Import $import, ?string $statut = null): Collection
    {
        return ImportLigne::where('import_id', $import->id)->when($statut, fn ($q) => $q->where('statut', $statut))->orderBy('numero')->get();
    }

    public function ligneOrFail(Import $import, int $numero): ImportLigne
    {
        return ImportLigne::where('import_id', $import->id)->where('numero', $numero)->firstOrFail();
    }

    public function saveLigne(ImportLigne $ligne): void
    {
        $ligne->save();
    }

    public function correspondances(int $ecoleId, EntiteImport $entite): array
    {
        return ImportCorrespondance::where('ecole_id', $ecoleId)->where('entite', $entite->value)->pluck('cible', 'entete')->all();
    }

    public function memoriserCorrespondance(int $ecoleId, EntiteImport $entite, string $entete, string $cible): void
    {
        ImportCorrespondance::updateOrCreate(
            ['ecole_id' => $ecoleId, 'entite' => $entite->value, 'entete' => $entete],
            ['cible' => $cible],
        );
    }
}
