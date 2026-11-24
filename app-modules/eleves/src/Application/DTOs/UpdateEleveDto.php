<?php

declare(strict_types=1);

namespace Modules\Eleves\Application\DTOs;

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
        /** @var array<string, mixed>|null null leaves the custom values untouched */
        public ?array $champsPerso = null,
    ) {}
}
