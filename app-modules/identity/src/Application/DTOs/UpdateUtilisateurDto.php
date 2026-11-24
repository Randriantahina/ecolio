<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTOs;

use Modules\Identity\Domain\Enums\Role;

/** Null means "leave unchanged". */
final readonly class UpdateUtilisateurDto
{
    public function __construct(
        public ?string $nom = null,
        public ?bool $actif = null,
        public ?Role $role = null,
    ) {}
}
