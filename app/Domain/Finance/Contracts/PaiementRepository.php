<?php

declare(strict_types=1);

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Models\Paiement;
use App\Domain\Finance\Models\Recu;
use Illuminate\Support\Collection;

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
