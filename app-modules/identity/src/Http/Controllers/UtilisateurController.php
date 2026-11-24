<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Identity\Application\Services\TeamService;
use Modules\Identity\Domain\Models\Utilisateur;
use Modules\Identity\Http\Requests\StoreUtilisateurRequest;
use Modules\Identity\Http\Requests\UpdateUtilisateurRequest;
use Modules\Identity\Http\Resources\UtilisateurResource;

final class UtilisateurController extends Controller
{
    public function __construct(private readonly TeamService $team) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('gererEquipe', Utilisateur::class);

        return UtilisateurResource::collection($this->team->lister($request->user()));
    }

    public function store(StoreUtilisateurRequest $request): JsonResponse
    {
        $membre = $this->team->creer($request->user(), $request->toDto());

        return response()->json(['utilisateur' => UtilisateurResource::make($membre)->resolve()], 201);
    }

    public function update(UpdateUtilisateurRequest $request, string $uuid): JsonResponse
    {
        $membre = $this->team->modifier($request->user(), $uuid, $request->toDto());

        return response()->json(['utilisateur' => UtilisateurResource::make($membre)->resolve()]);
    }
}
