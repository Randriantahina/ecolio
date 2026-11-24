<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTOs;

use Modules\Identity\Domain\Enums\Role;

final readonly class CreateUtilisateurDto
{
    public function __construct(
        public string $nom,
        public ?string $email,
        public ?string $telephone,
        public string $password,
        public Role $role,
    ) {}
}
