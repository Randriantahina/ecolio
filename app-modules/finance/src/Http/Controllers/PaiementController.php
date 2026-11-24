<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Eleves\Domain\Contracts\EleveRepository;
use Modules\Finance\Application\Services\PaiementService;
use Modules\Finance\Application\Services\RegistreEcolageService;
use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Contracts\RecuPdfGenerator;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Http\Requests\AnnulerPaiementRequest;
use Modules\Finance\Http\Requests\EncaisserRequest;
use Modules\Finance\Http\Resources\EcheanceResource;
use Modules\Finance\Http\Resources\PaiementResource;
use Symfony\Component\HttpFoundation\Response;

final class PaiementController extends Controller
{
    public function __construct(
        private readonly PaiementService $paiements,
        private readonly RegistreEcolageService $registre,
        private readonly EleveRepository $eleves,
        private readonly EcheanceRepository $echeances,
        private readonly RecuPdfGenerator $pdf,
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

    public function pdf(Request $request, string $uuid): Response
    {
        Gate::authorize('viewAny', Paiement::class);

        $recu = $this->resource($this->paiements->voir($request->user(), $uuid));

        return response($this->pdf->generate($recu), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu-'.$recu['numero'].'.pdf"',
        ]);
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
