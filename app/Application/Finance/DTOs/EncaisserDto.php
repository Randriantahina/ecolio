<?php

declare(strict_types=1);

namespace App\Application\Finance\DTOs;

final readonly class EncaisserDto
{
    /** @param  list<array{echeance_uuid:string, montant_ar:int}>  $lignes */
    public function __construct(
        public string $eleveUuid,
        public array $lignes,
        public string $mode,
        public string $datePaiement,
        public ?string $referenceExterne,
        public ?string $clientUuid,
    ) {}
}
