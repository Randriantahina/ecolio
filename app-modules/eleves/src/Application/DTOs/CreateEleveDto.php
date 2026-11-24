<?php

declare(strict_types=1);

namespace Modules\Eleves\Application\DTOs;

final readonly class CreateEleveDto
{
    public function __construct(
        public string $nom,
        public ?string $prenoms,
        public ?string $sexe,
        public ?string $dateNaissance,
        public ?string $lieuNaissance,
        public ?string $matricule,
        public ?string $classeUuid,
        public string $typeInscription,
        public bool $estRedoublant,
        public bool $confirmerDoublon,
    ) {}
}
