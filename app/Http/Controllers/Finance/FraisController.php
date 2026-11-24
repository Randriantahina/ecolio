<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Application\Finance\Services\FraisService;
use App\Domain\Finance\Models\Tarif;
use App\Domain\Finance\Models\TypeFrais;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TarifRequest;
use App\Http\Requests\Finance\TypeFraisRequest;
use App\Http\Resources\TarifResource;
use App\Http\Resources\TypeFraisResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
