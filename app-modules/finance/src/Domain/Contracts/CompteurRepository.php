<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Contracts;

interface CompteurRepository
{
    /**
     * Next value of the sequence `$cle` for a school, starting at 1.
     *
     * Must be called inside the transaction that consumes the number: if that transaction
     * rolls back the number is released too, which is what keeps receipts gapless (RG-R1).
     *
     * @throws \InvalidArgumentException when the key is empty
     */
    public function nextValue(int $ecoleId, string $cle): int;
}
