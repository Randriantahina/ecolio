<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Application\Finance\Services\PaiementService;
use App\Application\Finance\Services\RegistreEcolageService;
use App\Domain\Eleves\Contracts\EleveRepository;
use App\Domain\Finance\Contracts\EcheanceRepository;
use App\Domain\Finance\Models\Paiement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AnnulerPaiementRequest;
use App\Http\Requests\Finance\EncaisserRequest;
use App\Http\Resources\EcheanceResource;
use App\Http\Resources\PaiementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PaiementController extends Controller
{
    public function __construct(
        private readonly PaiementService $paiements,
        private readonly RegistreEcolageService $registre,
        private readonly EleveRepository $eleves,
        private readonly EcheanceRepository $echeances,
    ) {}

    public function store(EncaisserRequest $request): JsonResponse
    {
        $result = $this->paiements->encaisser($request->user(), $request->toDto());

        return response()->json(
            ['paiement' => $this->resource($result['paiement'])],
            $result['rejoue'] ? 200 : 201,
        );
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('viewAny', Paiement::class);

        return response()->json(['paiement' => $this->resource($this->paiements->voir($request->user(), $uuid))]);
    }

    public function annuler(AnnulerPaiementRequest $request, string $uuid): JsonResponse
    {
        $paiement = $this->paiements->annuler($request->user(), $uuid, $request->validated('motif'));

        return response()->json(['paiement' => $this->resource($paiement)]);
    }

    public function echeancesEleve(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('viewAny', Paiement::class);

        $eleve = $this->eleves->findOrFail($uuid, (int) $request->user()->ecole_id);
        $echeances = $this->echeances->forEleve($eleve->id);
        $payes = $this->echeances->paidAmounts($echeances->pluck('id')->all());

        return response()->json([
            'data' => $echeances->map(fn ($e) => EcheanceResource::make($e, $payes[$e->id] ?? 0)->resolve())->values()->all(),
            'reste_a_payer_ar' => $this->paiements->resteAPayer($eleve->id),
        ]);
    }

    public function registre(Request $request, string $uuid): JsonResponse
    {
        Gate::authorize('viewAny', Paiement::class);

        $periode = $request->query('periode') ? (string) $request->query('periode') : null;
        abort_if($periode !== null && ! preg_match('/^\d{4}-(\d{2}|T\d)$/', $periode), 422, 'Période invalide (AAAA-MM ou AAAA-Tn).');

        return response()->json(['data' => $this->registre->pour($request->user(), $uuid, $periode)]);
    }

    private function resource(Paiement $paiement): array
    {
        return (new PaiementResource($paiement, $this->paiements->resteAPayer($paiement->eleve_id)))->resolve();
    }
}
