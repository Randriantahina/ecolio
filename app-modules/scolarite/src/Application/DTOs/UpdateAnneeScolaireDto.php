<?php

declare(strict_types=1);

namespace Modules\Scolarite\Application\DTOs;

/** Null means "leave unchanged". */
final readonly class UpdateAnneeScolaireDto
{
    public function __construct(
        public ?string $libelle = null,
        public ?string $dateDebut = null,
        public ?string $dateFin = null,
    ) {}
}
