<?php

declare(strict_types=1);

namespace Modules\Scolarite\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Scolarite\Application\Services\AnneeScolaireService;
use Modules\Scolarite\Domain\Models\AnneeScolaire;
use Modules\Scolarite\Http\Requests\StoreAnneeScolaireRequest;
use Modules\Scolarite\Http\Requests\UpdateAnneeScolaireRequest;
use Modules\Scolarite\Http\Resources\AnneeScolaireResource;

final class AnneeScolaireController extends Controller
{
    public function __construct(private readonly AnneeScolaireService $annees) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AnneeScolaire::class);

        return AnneeScolaireResource::collection($this->annees->lister($request->user()));
    }

    public function store(StoreAnneeScolaireRequest $request): JsonResponse
    {
        $annee = $this->annees->creer($request->user(), $request->toDto());

        return response()->json(['annee_scolaire' => AnneeScolaireResource::make($annee)->resolve()], 201);
    }

    public function update(UpdateAnneeScolaireRequest $request, string $uuid): JsonResponse
    {
        $annee = $this->annees->modifier($request->user(), $uuid, $request->toDto());

        return response()->json(['annee_scolaire' => AnneeScolaireResource::make($annee)->resolve()]);
    }

    public function definirCourante(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('manage', AnneeScolaire::class);

        $annee = $this->annees->definirCourante($request->user(), $uuid);

        return response()->json(['annee_scolaire' => AnneeScolaireResource::make($annee)->resolve()]);
    }
}
