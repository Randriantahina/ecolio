<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Application\Scolarite\Services\AnneeScolaireService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\StoreAnneeScolaireRequest;
use App\Http\Requests\Scolarite\UpdateAnneeScolaireRequest;
use App\Http\Resources\AnneeScolaireResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
