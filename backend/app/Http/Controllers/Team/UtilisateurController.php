<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Application\Identity\Services\TeamService;
use App\Domain\Identity\Models\Utilisateur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreUtilisateurRequest;
use App\Http\Requests\Team\UpdateUtilisateurRequest;
use App\Http\Resources\UtilisateurResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
