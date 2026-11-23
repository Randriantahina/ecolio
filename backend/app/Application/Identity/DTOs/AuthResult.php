<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\Models\Utilisateur;

final readonly class AuthResult
{
    public function __construct(
        public string $token,
        public Utilisateur $utilisateur,
    ) {}
}
