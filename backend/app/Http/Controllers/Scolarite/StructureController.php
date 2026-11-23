<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Application\Scolarite\Services\StructureService;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\ClasseRequest;
use App\Http\Requests\Scolarite\NiveauRequest;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\NiveauResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
