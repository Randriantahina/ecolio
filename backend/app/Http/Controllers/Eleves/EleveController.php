<?php

declare(strict_types=1);

namespace App\Http\Controllers\Eleves;

use App\Application\Eleves\Services\EleveService;
use App\Application\Eleves\Services\InscriptionService;
use App\Domain\Eleves\Models\Eleve;
use App\Http\Controllers\Controller;
use App\Http\Requests\Eleves\EleveRequest;
use App\Http\Requests\Eleves\InscriptionRequest;
use App\Http\Resources\EleveResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class EleveController extends Controller
{
    public function __construct(
        private readonly EleveService $eleves,
        private readonly InscriptionService $inscriptions,
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
