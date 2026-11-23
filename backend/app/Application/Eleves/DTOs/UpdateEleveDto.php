<?php

declare(strict_types=1);

namespace App\Application\Eleves\DTOs;

/** Null means "leave unchanged". */
final readonly class UpdateEleveDto
{
    public function __construct(
        public ?string $nom = null,
        public ?string $prenoms = null,
        public ?string $sexe = null,
        public ?string $dateNaissance = null,
        public ?string $lieuNaissance = null,
        public ?string $matricule = null,
    ) {}
}
