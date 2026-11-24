<?php

declare(strict_types=1);

namespace App\Application\Scolarite\DTOs;

/** Null means "leave unchanged". */
final readonly class UpdateAnneeScolaireDto
{
    public function __construct(
        public ?string $libelle = null,
        public ?string $dateDebut = null,
        public ?string $dateFin = null,
    ) {}
}
