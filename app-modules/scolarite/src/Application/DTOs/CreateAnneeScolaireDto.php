<?php

declare(strict_types=1);

namespace Modules\Scolarite\Application\DTOs;

final readonly class CreateAnneeScolaireDto
{
    public function __construct(
        public string $libelle,
        public string $dateDebut,
        public string $dateFin,
        public bool $estCourante,
    ) {}
}
