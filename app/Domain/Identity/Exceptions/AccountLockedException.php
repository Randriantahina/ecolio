<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class AccountLockedException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Account temporarily locked.');
    }
}
