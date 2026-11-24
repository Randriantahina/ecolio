<?php

declare(strict_types=1);

namespace Modules\Identity\Application\DTOs;

final readonly class LoginDto
{
    public function __construct(
        public string $login,
        public string $password,
        public string $deviceName = 'web',
    ) {}
}
