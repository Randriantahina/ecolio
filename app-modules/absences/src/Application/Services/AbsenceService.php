<?php

declare(strict_types=1);

namespace Modules\Absences\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Absences\Domain\Contracts\AbsenceRepository;
use Modules\Absences\Domain\Models\Absence;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Scolarite\Domain\Contracts\StructureRepository;

final class AbsenceService
{
    public function __construct(
        private readonly AbsenceRepository $absences,
        private readonly StructureRepository $structure,
        private readonly EleveRepository $eleves,
    ) {}

    /** @return Collection<int, Absence> */
    public function du(Utilisateur $acteur, string $classeUuid, string $date, string $periode): Collection
    {
        $classe = $this->structure->classeOrFail($classeUuid, (int) $acteur->ecole_id);

        return $this->absences->forClasseDate((int) $acteur->ecole_id, $classe->id, $date, $periode);
    }

    /** @return array<string,int> The last $jours days ending today, oldest first, zero-filled for the chart. */
    public function parJour(Utilisateur $acteur, int $jours): array
    {
        $fin = now()->startOfDay();
        $debut = $fin->copy()->subDays($jours - 1);
        $comptes = $this->absences->countParJour((int) $acteur->ecole_id, $debut->toDateString(), $fin->toDateString());

        $serie = [];
        for ($jour = $debut->copy(); $jour <= $fin; $jour->addDay()) {
            $serie[$jour->toDateString()] = $comptes[$jour->toDateString()] ?? 0;
        }

        return $serie;
    }

    /**
     * "Everyone present, then untick the absentees" (spec 0.1): the screen sends the absentees only and
     * the stored set becomes exactly that list. Removing one is the plain delete of RG-P5.
     *
     * @param  list<array{eleve_uuid:string, justifiee?:bool, motif?:?string, client_uuid?:?string}>  $absents
     * @return Collection<int, Absence>
     */
    public function saisir(Utilisateur $acteur, string $classeUuid, string $date, string $periode, array $absents): Collection
    {
        return DB::transaction(function () use ($acteur, $classeUuid, $date, $periode, $absents): Collection {
            $ecoleId = (int) $acteur->ecole_id;
            $classe = $this->structure->classeOrFail($classeUuid, $ecoleId);
            $existantes = $this->absences->forClasseDate($ecoleId, $classe->id, $date, $periode)->keyBy(fn (Absence $a) => $a->eleve->uuid);

            $garder = [];
            foreach ($absents as $i => $ligne) {
                $eleve = $this->eleves->findOrFail($ligne['eleve_uuid'], $ecoleId);
                if ($eleve->inscriptionActive?->classe_id !== $classe->id) {
                    throw ValidationException::withMessages(["absents.$i.eleve_uuid" => 'Cet élève n\'est pas inscrit dans cette classe.']);
                }

                $garder[] = $eleve->uuid;
                $absence = $existantes->get($eleve->uuid) ?? new Absence;
                if (! $absence->exists) {
                    if (! empty($ligne['client_uuid']) && $this->absences->findByClientUuid($ligne['client_uuid'], $ecoleId) !== null) {
                        continue; // replayed from the offline queue
                    }
                    $absence->ecole_id = $acteur->ecole_id;
                    $absence->eleve_id = $eleve->id;
                    $absence->classe_id = $classe->id;
                    $absence->date = $date;
                    $absence->periode = $periode;
                    $absence->saisi_par = $acteur->id;
                    $absence->client_uuid = $ligne['client_uuid'] ?? null;
                }
                $absence->justifiee = (bool) ($ligne['justifiee'] ?? false);
                $absence->motif = $ligne['motif'] ?? null;
                $this->absences->save($absence);
            }

            $existantes->reject(fn (Absence $a, string $uuid) => in_array($uuid, $garder, true))
                ->each(fn (Absence $a) => $this->absences->delete($a));

            return $this->absences->forClasseDate($ecoleId, $classe->id, $date, $periode);
        });
    }
}
