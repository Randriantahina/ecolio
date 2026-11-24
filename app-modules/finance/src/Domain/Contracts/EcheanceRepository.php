<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Finance\Domain\Models\Echeance;

interface EcheanceRepository
{
    public function save(Echeance $echeance): void;

    public function exists(int $inscriptionId, int $typeFraisId, ?string $periode): bool;

    /**
     * Rows locked for update: two cashiers paying the same instalment are serialised, so the
     * balance check below cannot be raced into an overpayment.
     *
     * @param  list<string>  $uuids
     * @return Collection<int, Echeance>
     */
    public function lockByUuids(array $uuids, int $ecoleId): Collection;

    /** Sum of the lines of VALID payments only: a void gives the money back to the balance. */
    public function paidAmount(int $echeanceId): int;

    /**
     * Batch form of paidAmount for the register screen (one query, not one per box).
     *
     * @param  list<int>  $ids
     * @return array<int,int> echeance id => ariary paid
     */
    public function paidAmounts(array $ids): array;

    /**
     * Latest VALID receipt per instalment: its number is shown inside the ticked box and its payment
     * uuid lets the screen void it (unchecking, RG-P4).
     *
     * @param  list<int>  $ids
     * @return array<int,array{numero:string,paiement_uuid:string}> echeance id => receipt
     */
    public function lastReceiptNumbers(array $ids): array;

    /**
     * RG-P6: marks as cancelled every monthly instalment AFTER `$moisSortie` (`YYYY-MM`) that no valid
     * payment has touched. Returns how many were cancelled.
     */
    public function cancelFutureUnpaid(int $eleveId, string $moisSortie): int;

    /** @return Collection<int, Echeance> */
    public function forEleve(int $eleveId): Collection;

    /** @return Collection<int, Echeance> */
    public function forClassePeriode(int $ecoleId, int $classeId, ?string $periode): Collection;
}
