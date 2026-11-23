<?php

declare(strict_types=1);

namespace App\Domain\Eleves\Contracts;

use App\Domain\Eleves\Models\Inscription;

interface InscriptionRepository
{
    public function activeFor(int $eleveId, int $anneeId): ?Inscription;

    public function save(Inscription $inscription): void;

    public function countActiveInClasse(int $classeId): int;

    public function recordClassChange(int $ecoleId, int $inscriptionId, int $ancienneClasseId, int $nouvelleClasseId, ?int $parUtilisateurId): void;
}
