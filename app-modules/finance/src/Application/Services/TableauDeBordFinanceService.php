<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Finance\Domain\Contracts\EcheanceRepository;
use Modules\Finance\Domain\Contracts\PaiementRepository;
use Modules\Identity\Domain\Models\Utilisateur;

/** The three numbers a directeur wants on opening the app: cash in this month, what is owed, how many are late. */
final class TableauDeBordFinanceService
{
    public function __construct(
        private readonly PaiementRepository $paiements,
        private readonly EcheanceRepository $echeances,
    ) {}

    /** @return array{encaisse_mois_ar:int, reste_a_payer_ar:int, echeances_impayees:int} ariary, integers */
    public function pour(Utilisateur $acteur): array
    {
        $ecoleId = (int) $acteur->ecole_id;

        return [
            'encaisse_mois_ar' => $this->paiements->totalEncaisse($ecoleId, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()),
            'reste_a_payer_ar' => $this->echeances->resteAPayerTotal($ecoleId),
            'echeances_impayees' => $this->echeances->countImpayees($ecoleId),
        ];
    }
}
