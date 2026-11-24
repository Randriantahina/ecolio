<?php

declare(strict_types=1);

namespace Modules\Platform\Application\DTOs;

/** Null means "leave unchanged". */
final readonly class UpdateEcoleDto
{
    public function __construct(
        public ?string $nom = null,
        public ?string $adresse = null,
        public ?string $telephone = null,
        public ?string $email = null,
        public ?string $mentionLegale = null,
        public ?string $signatureRecu = null,
    ) {}
}
