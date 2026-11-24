<?php

declare(strict_types=1);

namespace Modules\Eleves\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Eleves\Application\Services\DepartService;
use Modules\Eleves\Application\Services\EleveService;
use Modules\Eleves\Application\Services\InscriptionService;
use Modules\Eleves\Domain\Enums\StatutEleve;
use Modules\Eleves\Domain\Models\Eleve;
use Modules\Eleves\Http\Requests\EleveRequest;
use Modules\Eleves\Http\Requests\InscriptionRequest;
use Modules\Eleves\Http\Requests\SortieRequest;
use Modules\Eleves\Http\Resources\EleveResource;

final class EleveController extends Controller
{
    public function __construct(
        private readonly EleveService $eleves,
        private readonly InscriptionService $inscriptions,
        private readonly DepartService $departs,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Eleve::class);

        return EleveResource::collection($this->eleves->lister($request->user(), [
            'q' => $request->query('q'),
            'classe' => $request->query('classe'),
            'statut' => $request->query('statut'),
        ], min(100, max(1, (int) $request->query('per_page', 50)))));
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('viewAny', Eleve::class);

        return response()->json(['eleve' => EleveResource::make($this->eleves->voir($request->user(), $uuid))->resolve()]);
    }

    public function store(EleveRequest $request): JsonResponse
    {
        $result = $this->eleves->creer($request->user(), $request->toCreateDto());

        return response()->json([
            'eleve' => EleveResource::make($result['eleve'])->resolve(),
            'avertissements' => ['effectif_depasse' => $result['effectif_depasse']],
        ], 201);
    }

    public function update(EleveRequest $request, string $uuid): JsonResponse
    {
        return response()->json([
            'eleve' => EleveResource::make($this->eleves->modifier($request->user(), $uuid, $request->toUpdateDto()))->resolve(),
        ]);
    }

    public function inscrire(InscriptionRequest $request, string $uuid): JsonResponse
    {
        $eleve = $this->eleves->voir($request->user(), $uuid);
        $result = $this->inscriptions->inscrire($request->user(), $eleve, $request->validated('classe_uuid'),
            $request->validated('type', 'nouvelle'), (bool) $request->validated('est_redoublant', false));

        return response()->json([
            'eleve' => EleveResource::make($this->eleves->voir($request->user(), $uuid))->resolve(),
            'avertissements' => ['effectif_depasse' => $result['effectif_depasse']],
        ], 201);
    }

    public function sortie(SortieRequest $request, string $uuid): JsonResponse
    {
        $d = $request->validated();
        $eleve = $this->departs->enregistrer($request->user(), $uuid, StatutEleve::from($d['statut']), $d['date_sortie'],
            $d['motif_sortie'] ?? null, $d['commentaire_sortie'] ?? null);

        return response()->json(['eleve' => EleveResource::make($eleve)->resolve()]);
    }

    public function changerClasse(InscriptionRequest $request, string $uuid): JsonResponse
    {
        $eleve = $this->eleves->voir($request->user(), $uuid);
        $result = $this->inscriptions->changerClasse($request->user(), $eleve, $request->validated('classe_uuid'));

        return response()->json([
            'eleve' => EleveResource::make($this->eleves->voir($request->user(), $uuid))->resolve(),
            'avertissements' => ['effectif_depasse' => $result['effectif_depasse']],
        ]);
    }
}
