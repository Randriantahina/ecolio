<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Scolarite\Application\Services\StructureService;
use Modules\Scolarite\Domain\Models\Classe;
use Modules\Scolarite\Domain\Models\Niveau;
use Modules\Scolarite\Http\Requests\ClasseRequest;
use Modules\Scolarite\Http\Requests\NiveauRequest;
use Modules\Scolarite\Http\Resources\ClasseResource;
use Modules\Scolarite\Http\Resources\NiveauResource;

final class StructureController extends Controller
{
    public function __construct(private readonly StructureService $structure) {}

    public function niveaux(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Niveau::class);

        return NiveauResource::collection($this->structure->niveaux($request->user()));
    }

    public function storeNiveau(NiveauRequest $request): JsonResponse
    {
        $niveau = $this->structure->creerNiveau($request->user(), $request->validated());

        return response()->json(['niveau' => NiveauResource::make($niveau)->resolve()], 201);
    }

    public function updateNiveau(NiveauRequest $request, string $uuid): JsonResponse
    {
        $niveau = $this->structure->modifierNiveau($request->user(), $uuid, $request->validated());

        return response()->json(['niveau' => NiveauResource::make($niveau)->resolve()]);
    }

    public function classes(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Classe::class);

        return ClasseResource::collection(
            $this->structure->classes($request->user(), $request->query('annee') ? (string) $request->query('annee') : null),
        );
    }

    public function storeClasse(ClasseRequest $request): JsonResponse
    {
        $classe = $this->structure->creerClasse($request->user(), $request->validated());

        return response()->json(['classe' => ClasseResource::make($classe)->resolve()], 201);
    }

    public function updateClasse(ClasseRequest $request, string $uuid): JsonResponse
    {
        $classe = $this->structure->modifierClasse($request->user(), $uuid, $request->validated());

        return response()->json(['classe' => ClasseResource::make($classe)->resolve()]);
    }

    public function destroyClasse(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('manage', Classe::class);
        $this->structure->supprimerClasse($request->user(), $uuid);

        return response()->json(null, 204);
    }
}
