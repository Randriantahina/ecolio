<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Finance\Application\Services\FraisService;
use Modules\Finance\Domain\Models\Tarif;
use Modules\Finance\Domain\Models\TypeFrais;
use Modules\Finance\Http\Requests\TarifRequest;
use Modules\Finance\Http\Requests\TypeFraisRequest;
use Modules\Finance\Http\Resources\TarifResource;
use Modules\Finance\Http\Resources\TypeFraisResource;

final class FraisController extends Controller
{
    public function __construct(private readonly FraisService $frais) {}

    public function types(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TypeFrais::class);

        return TypeFraisResource::collection($this->frais->types($request->user()));
    }

    public function storeType(TypeFraisRequest $request): JsonResponse
    {
        return response()->json(['type_frais' => TypeFraisResource::make($this->frais->creerType($request->user(), $request->validated()))->resolve()], 201);
    }

    public function updateType(TypeFraisRequest $request, string $uuid): JsonResponse
    {
        return response()->json(['type_frais' => TypeFraisResource::make($this->frais->modifierType($request->user(), $uuid, $request->validated()))->resolve()]);
    }

    public function tarifs(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Tarif::class);

        return TarifResource::collection($this->frais->tarifs($request->user(), $request->query('annee') ? (string) $request->query('annee') : null));
    }

    public function storeTarif(TarifRequest $request): JsonResponse
    {
        return response()->json(['tarif' => TarifResource::make($this->frais->creerTarif($request->user(), $request->validated()))->resolve()], 201);
    }

    public function updateTarif(TarifRequest $request, string $uuid): JsonResponse
    {
        return response()->json(['tarif' => TarifResource::make($this->frais->modifierTarif($request->user(), $uuid, (int) $request->validated('montant_ar')))->resolve()]);
    }

    public function destroyTarif(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('manage', Tarif::class);
        $this->frais->supprimerTarif($request->user(), $uuid);

        return response()->json(null, 204);
    }
}
