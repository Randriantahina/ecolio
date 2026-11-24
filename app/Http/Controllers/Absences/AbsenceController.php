<?php

declare(strict_types=1);

namespace App\Http\Controllers\Absences;

use App\Application\Absences\Services\AbsenceService;
use App\Domain\Absences\Models\Absence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Absences\SaisirAbsencesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class AbsenceController extends Controller
{
    public function __construct(private readonly AbsenceService $absences) {}

    public function index(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('viewAny', Absence::class);
        $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'periode' => ['sometimes', 'in:journee,matin,apres_midi']]);

        return response()->json(['data' => $this->rows($this->absences->du(
            $request->user(), $uuid, (string) $request->query('date'), (string) $request->query('periode', 'journee'),
        ))]);
    }

    public function saisir(SaisirAbsencesRequest $request, string $uuid): JsonResponse
    {
        $d = $request->validated();

        return response()->json(['data' => $this->rows($this->absences->saisir(
            $request->user(), $uuid, $d['date'], $d['periode'] ?? 'journee', $d['absents'],
        ))]);
    }

    /** @param  Collection<int, Absence>  $absences */
    private function rows(Collection $absences): array
    {
        return $absences->map(fn (Absence $a) => [
            'eleve_uuid' => $a->eleve->uuid,
            'matricule' => $a->eleve->matricule,
            'justifiee' => $a->justifiee,
            'motif' => $a->motif,
        ])->values()->all();
    }
}
