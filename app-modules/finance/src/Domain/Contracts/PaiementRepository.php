<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Finance\Domain\Models\Paiement;
use Modules\Finance\Domain\Models\Recu;

interface PaiementRepository
{
    public function save(Paiement $paiement): void;

    /** @param  list<array{echeance_id:int, montant_ar:int}>  $lignes */
    public function saveLignes(Paiement $paiement, array $lignes): void;

    public function saveRecu(Recu $recu): void;

    public function findByClientUuid(string $clientUuid, int $ecoleId): ?Paiement;

    /** Throws ModelNotFoundException for another school's payment. */
    public function findOrFail(string $uuid, int $ecoleId): Paiement;

    /** Locks the row so a concurrent void and replay cannot both succeed. */
    public function findForUpdateOrFail(string $uuid, int $ecoleId): Paiement;

    /** @return Collection<int, Paiement> */
    public function forEleve(int $eleveId): Collection;
}
