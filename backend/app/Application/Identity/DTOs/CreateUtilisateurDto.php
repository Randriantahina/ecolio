<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\Enums\Role;

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
