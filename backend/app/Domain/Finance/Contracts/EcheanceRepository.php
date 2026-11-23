<?php

declare(strict_types=1);

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Models\Echeance;
use Illuminate\Support\Collection;

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
     * Latest VALID receipt number per instalment, shown inside the ticked box.
     *
     * @param  list<int>  $ids
     * @return array<int,string> echeance id => numero_affiche
     */
    public function lastReceiptNumbers(array $ids): array;

    /** @return Collection<int, Echeance> */
    public function forEleve(int $eleveId): Collection;

    /** @return Collection<int, Echeance> */
    public function forClassePeriode(int $ecoleId, int $classeId, ?string $periode): Collection;
}
